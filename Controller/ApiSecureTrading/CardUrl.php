<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
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

class CardUrl extends Response implements CsrfAwareActionInterface
{

	protected $jsonFactory;

	protected $orderFactory;

	protected $jwt;

	protected $logger;

	protected $config;

	protected $scopeConfig;

	public function __construct(Context $context, ConfigInterface $config, \Magento\Sales\Model\OrderFactory $orderFactory, Session $checkoutSession, CommandPoolInterface $commandPool, PaymentTokenFactoryInterface $paymentTokenFactory, OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory, Logger $logger, JWT $jwt, Json $json, SubscriptionFactory $subscriptionFactory, SubscriptionHelper $subscriptionHelper, MultiShippingFactory $multiShippingFactory, SerializerInterface $serializer, OrderSender $orderSender, ScopeConfigInterface $scopeConfig)
	{
		parent::__construct($context, $config, $orderFactory, $checkoutSession, $commandPool, $paymentTokenFactory, $extensionInterfaceFactory, $logger, $jwt, $json, $subscriptionFactory, $subscriptionHelper, $multiShippingFactory, $serializer, $orderSender);
		$this->scopeConfig = $scopeConfig;
	}

	public function execute()
	{
		$this->logger->debug('--- Notification Response API Secure Trading---');
		try {
			$responseParams = $this->getRequest()->getParams();
			$this->logger->debug('--- Notification Response API Secure Trading Params: ', array($responseParams));
			if (!empty($responseParams)) {
				$response = $this->decodeJWT($responseParams);
				$responseData = get_object_vars($response->payload->response[0]);

				$payload = $this->decodeJWT(get_object_vars($response->payload));
				$payload = get_object_vars($payload->payload);
				$payload = array_merge($payload, $responseData);

				$incrementId = $this->getRequest()->getParam('orderreference');
				$order = $this->orderFactory->create()->loadByIncrementId($incrementId);

				$payment = $order->getPayment();
				$payment->setAdditionalInformation('api_secure_trading_data', $responseParams);

				if(!empty($payment->getAdditionalInformation('secure_trading_data'))){
					$dataAdd = $payment->getAdditionalInformation('secure_trading_data');
				}

				if(!empty($payment->getAdditionalInformation('multishipping_data')) && !empty($payment->getAdditionalInformation('multishipping_set_id'))){
					$dataMultishipping = $payment->getAdditionalInformation('multishipping_data');
				}

				$paymentAction = $this->scopeConfig->getValue('payment/api_secure_trading/api_payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

				$multiShippingSetId = isset($dataMultishipping['multishippingsetid']) ? $dataMultishipping['multishippingsetid'] : null;
				$isMultiShipping = isset($dataMultishipping['ismultishipping']) ? $dataMultishipping['ismultishipping'] : 0;
				$isSubscription = isset($dataAdd['subscriptiontype']) ? 1 : 0;

				if (empty($order->getId())) {
					$this->messageManager->addError(__("Something went wrong. Please try again later."));
				}

				$this->logger->debug('--- Notification Response Error Code: ' . $payload['errorcode'] . '---');
				if ($payload['errorcode'] === "0") {
					if ($isMultiShipping == 1 && $multiShippingSetId != null) {
						$this->processMultiShipping($multiShippingSetId, $payload);
						$this->restoredMultiShippingCheckoutSession($multiShippingSetId);
						$redirectPath = 'multishipping/checkout/success';
					} else if ($isSubscription == 1) {
						//Process Subscription Orders
						/** @var Order\Payment $payment */
						$subscriptionFactory = $this->subscriptionFactory->create();
						$payment = $order->getPayment();
						foreach ($payload as $key => $param) {
							$payment->setAdditionalInformation($key, $param);
						}

						//save child subscription
						if(isset($response->payload->response[1])){
							$dataChildSubs = get_object_vars($response->payload->response[1]);
							$payment->setAdditionalInformation('nextrecurtransaction', $dataChildSubs['transactionreference']);
						}

						$stData = $payment->getAdditionalInformation('secure_trading_data');
						$array  = array_merge($payload, $stData);
						$paymentAction = $payment->getAdditionalInformation('payment_action');
						$this->processSubscription($payment, $array);
						$subscriptionFactory->loadByTransactionId($payment->getAdditionalInformation('transactionreference'));
						if($subscriptionFactory->getId()){
							$payment->setAdditionalInformation('subscriptionid', $subscriptionFactory->getId());
						}
						if (!empty($stData['issubscription'])) {
							if ($stData['subscriptiontype'] == 'INSTALLMENT') {
								$this->commandPool->get('capture_partial')->execute(['order' => $order, 'info' => $array]);
							} else {
								$this->commandPool->get($paymentAction)->execute(['order' => $order, 'info' => $responseData]);
							}
						}
						$this->restoredCheckoutSession($order);
						$redirectPath = 'checkout/onepage/success';
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

						$this->commandPool->get($paymentAction)->execute(['order' => $order, 'info' => $responseData]);

						$this->restoredCheckoutSession($order);
						$redirectPath = 'checkout/onepage/success';

						$this->sendEmailAfterPayment($order);
					}
					if ($payment->getAdditionalInformation('save_card_info_api') == 1){
						$detailData = $this->commandPool->get('detail_transaction')->execute(['payment'=>$payment]);
						$responseData['expirydate'] = $detailData['expirydate'];
						$this->subscriptionHelper->saveCreditCard($responseData, $payment, $order);
					}
					return $this->redirect('', $redirectPath);
				} else {
					$order->cancel();
					$order->addCommentToStatusHistory(__('Invalid response.'));
					$order->save();
					$this->logger->debug('--- Notification Response Error: Invalid response.');
					return $this->redirect('', 'checkout/cart');
				}
			}
		} catch (\Exception $e) {
			$this->logger->addDebug('API SecureTrading Error:' . $e->getMessage());
            return $this->redirect('', 'checkout/cart');
		}
	}


	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
	{
		return null;
	}

	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
	}

	public function decodeJWT($jwtResponse)
	{
		$responseData = $this->jwt->decode($jwtResponse['jwt'], $this->config->getValue(Data::JWT_SECRET_KEY), ['HS256']);
		return $responseData;
	}
}
