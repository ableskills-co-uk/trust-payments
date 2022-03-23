<?php
namespace SecureTrading\Trust\Controller\ApiSecureTrading;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order;
use Firebase\JWT\JWT;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use SecureTrading\Trust\Controller\PaymentPage\Response;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Gateway\Config\Config;
use SecureTrading\Trust\Helper\SubscriptionHelper;
use SecureTrading\Trust\Model\MultiShippingFactory;
use SecureTrading\Trust\Model\SubscriptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ReturnUrlPayPal extends Response
{

	protected $jsonFactory;

	protected $orderFactory;

	protected $jwt;

	protected $logger;

	protected $config;

	protected $scopeConfig;

	function __construct(Context $context, ConfigInterface $config, \Magento\Sales\Model\OrderFactory $orderFactory, Session $checkoutSession, CommandPoolInterface $commandPool, PaymentTokenFactoryInterface $paymentTokenFactory, OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory, Logger $logger, JWT $jwt, Json $json, SubscriptionFactory $subscriptionFactory, SubscriptionHelper $subscriptionHelper, MultiShippingFactory $multiShippingFactory, SerializerInterface $serializer, OrderSender $orderSender, ScopeConfigInterface $scopeConfig)
	{
		parent::__construct($context, $config, $orderFactory, $checkoutSession, $commandPool, $paymentTokenFactory, $extensionInterfaceFactory, $logger, $jwt, $json, $subscriptionFactory, $subscriptionHelper, $multiShippingFactory, $serializer, $orderSender);
		$this->scopeConfig = $scopeConfig;
	}

	function execute()
	{
		$this->logger->debug('--- Notification Response PayPal Secure Trading---');
		try {
			$responseParams = $this->getRequest()->getParams();
			$this->logger->debug('--- Notification Response Get Detail PayPal: ', array($responseParams));
			if (!empty($responseParams)) {
				$orderDetail = $this->commandPool->get('get_detail_paypal')->execute(['reponseParams' => $responseParams]);
				$this->logger->debug('--- Response Get Detail PayPal: ', array($responseParams));
				if($orderDetail['errorcode'] == 0){
					$incrementId = $orderDetail['orderreference'];
					$order = $this->orderFactory->create()->loadByIncrementId($incrementId);
					$payment = $order->getPayment();
					
					foreach ( $responseParams as $key => $value){
						$payment->setAdditionalInformation($key, $value);
					}
					//AUTH PAYPAL
					$authPayPal = $this->commandPool->get('auth_paypal')->execute(['reponseParams' => $responseParams]);
					
					if($authPayPal['errorcode'] == 0) {
						$payment = $order->getPayment();
						foreach ($authPayPal as $key => $value) {
							if($key == 'rules'){
								$value = $this->json->serialize($value);
							}
							$payment->setAdditionalInformation($key, $value);
						}
						$payment->save();
						$payment->setAdditionalInformation('api_secure_trading_data', $authPayPal);

						$paymentAction = $this->scopeConfig->getValue('payment/api_secure_trading/api_payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

						if (empty($payment->getAdditionalInformation('payment_action'))) {
							$payment->setAdditionalInformation('payment_action', $paymentAction);
						}

						if(!empty($payment->getAdditionalInformation('secure_trading_data'))){
							$dataAdd = $payment->getAdditionalInformation('secure_trading_data');
						}

						if(!empty($payment->getAdditionalInformation('multishipping_data')) && !empty($payment->getAdditionalInformation('multishipping_set_id'))){
							$dataMultishipping = $payment->getAdditionalInformation('multishipping_data');
						}

						$multiShippingSetId = isset($dataMultishipping['multishippingsetid']) ? $dataMultishipping['multishippingsetid'] : null;
						$isMultiShipping = isset($dataMultishipping['ismultishipping']) ? $dataMultishipping['ismultishipping'] : 0;

						if ($isMultiShipping == 1 && $multiShippingSetId != null) {
							$this->processMultiShipping($multiShippingSetId, $authPayPal);
							$this->restoredMultiShippingCheckoutSession($multiShippingSetId);
							$redirectPath = 'multishipping/checkout/success';
						} else {
							$this->commandPool->get($paymentAction)->execute(['order' => $order, 'info' => $authPayPal]);
							$redirectPath = 'checkout/onepage/success';
							$this->sendEmailAfterPayment($order);
						}
						return $this->redirect('', $redirectPath);
					}else{
						$this->logger->addDebug('Get Auth PayPal Error:' . $authPayPal['errormessage']);
					}
				}else{
					$this->logger->addDebug('Get Detail PayPal Error:' . $responseParams['errormessage']);
				}
				return $this->redirect('', 'checkout/cart');
			}
		} catch (\Exception $e) {
			$this->logger->addDebug('API SecureTrading Error:' . $e->getMessage());
			throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
		}
	}
}
