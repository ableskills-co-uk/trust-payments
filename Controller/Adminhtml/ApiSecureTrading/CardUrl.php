<?php
namespace SecureTrading\Trust\Controller\Adminhtml\ApiSecureTrading;
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
use Magento\Framework\Message\ManagerInterface;

class CardUrl extends Response
{

	protected $jsonFactory;

	protected $orderFactory;

	protected $jwt;

	protected $logger;

	protected $config;

	protected $scopeConfig;

	protected $messageManager;

	function __construct(Context $context, ConfigInterface $config, \Magento\Sales\Model\OrderFactory $orderFactory, Session $checkoutSession, CommandPoolInterface $commandPool, PaymentTokenFactoryInterface $paymentTokenFactory, OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory, Logger $logger, JWT $jwt, Json $json, SubscriptionFactory $subscriptionFactory, SubscriptionHelper $subscriptionHelper, MultiShippingFactory $multiShippingFactory, SerializerInterface $serializer, OrderSender $orderSender, ScopeConfigInterface $scopeConfig, ManagerInterface $messageManager)
	{
		parent::__construct($context, $config, $orderFactory, $checkoutSession, $commandPool, $paymentTokenFactory, $extensionInterfaceFactory, $logger, $jwt, $json, $subscriptionFactory, $subscriptionHelper, $multiShippingFactory, $serializer, $orderSender);
		$this->scopeConfig = $scopeConfig;
		$this->messageManager = $messageManager;
	}

	function execute()
	{
		$this->logger->debug('--- Notification Response API Secure Trading---');
		try {
			$responseParams = $this->getRequest()->getParams();
			$this->logger->debug('--- Notification Response API Secure Trading Params: ', array($responseParams));
			if (!empty($responseParams)) {
				$response = $this->decodeJWT($responseParams);
				$responseData = get_object_vars($response->payload->response[0]);

				$incrementId = $this->getRequest()->getParam('orderreference');
				$order = $this->orderFactory->create()->loadByIncrementId($incrementId);

				$payment = $order->getPayment();
				$payment->setAdditionalInformation('api_secure_trading_data', $responseParams);

				if(!empty($payment->getAdditionalInformation('secure_trading_data'))){
					$dataAdd = $payment->getAdditionalInformation('secure_trading_data');
				}

				$paymentAction = $this->scopeConfig->getValue('payment/api_secure_trading/api_payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

				$multiShippingSetId = isset($dataAdd['multishippingsetid']) ? $dataAdd['multishippingsetid'] : null;
				$isMultiShipping = isset($dataAdd['ismultishipping']) ? $dataAdd['ismultishipping'] : 0;
				$isSubscription = isset($dataAdd['subscriptiontype']) ? 1 : 0;

				if (empty($order->getId())) {
					$this->messageManager->addError(__("Something went wrong. Please try again later."));
				}

				$this->logger->debug('--- Notification Response Error Code: ' . $responseData['errorcode'] . '---');
				if ($responseData['errorcode'] === "0") {
					if ($isMultiShipping == 1 && $multiShippingSetId != null) {
						$this->processMultiShipping($multiShippingSetId, $responseData);
						$this->restoredMultiShippingCheckoutSession($multiShippingSetId);
						$redirectPath = 'multishipping/checkout/success';
					} else if ($isSubscription == 1) {
						//Process Subscription Orders
						/** @var Order\Payment $payment */
						$payment = $order->getPayment();
						foreach ($responseData as $key => $param) {
							$payment->setAdditionalInformation($key, $param);
						}
						$stData = $payment->getAdditionalInformation('secure_trading_data');
						$array  = array_merge($responseData, $stData);
						$this->processSubscription($payment, $array);
						if (!empty($stData['issubscription'])) {
							if ($stData['subscriptiontype'] == 'INSTALLMENT') {
								$this->commandPool->get('capture_partial')->execute(['order' => $order, 'info' => $responseData]);
							} else {
								$this->commandPool->get($paymentAction)->execute(['order' => $order, 'info' => $responseData]);
							}
						}
						$this->restoredCheckoutSession($order);
						$redirectPath = 'sales/order/view';
						$this->sendEmailAfterPayment($order);
					} else {
						//Process Normal Orders
						/** @var Order\Payment $payment */
						$payment = $order->getPayment();
						foreach ($responseData as $key => $param) {
							$payment->setAdditionalInformation($key, $param);
						}

						if (empty($payment->getAdditionalInformation('payment_action'))){
							$payment->setAdditionalInformation('payment_action', $paymentAction);
						}

						if (!empty($payment->getAdditionalInformation('transactionreference'))){
							$payment->setAdditionalInformation('transactionreference', $responseParams['transactionreference']);
						}

						$this->commandPool->get($paymentAction)->execute(['order' => $order, 'info' => $responseData]);

						$this->restoredCheckoutSession($order);
						$redirectPath = 'sales/order/view';

						$this->sendEmailAfterPayment($order);
					}
                    if ($payment->getAdditionalInformation('save_card_info_api') == 1)
                        $this->subscriptionHelper->saveCreditCard($responseData, $payment, $order);
                    $this->messageManager->addSuccessMessage(__('You created the order.'));
                    return $this->resultRedirectFactory->create()->setPath($redirectPath, ['order_id' => $order->getId()]);
				} else {
					$order->cancel();
					$order->addCommentToStatusHistory(__('Invalid response.'));
					$order->save();
					$this->logger->debug('--- Notification Response Error: Invalid response.');
					return $this->redirect('', 'sales/order');
				}
			}
		} catch (\Exception $e) {
			$this->logger->addDebug('API SecureTrading Error:' . $e->getMessage());
			throw new \LocalizedException('Something went wrong');
		}
	}

	function decodeJWT($jwtResponse)
	{
        # 2022-03-18 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		$responseData = $this->jwt->decode($jwtResponse['jwt'],
            new \Firebase\JWT\Key('2-349c1a844b3bbb26452d5b6ffd25237ba9f4c42b109087acc9d72e6d90ab77f8', 'HS256')
        );
		return $responseData;
	}
}
