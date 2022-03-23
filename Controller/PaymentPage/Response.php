<?php

namespace SecureTrading\Trust\Controller\PaymentPage;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Helper\Data;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Firebase\JWT\JWT;
use SecureTrading\Trust\Model\MultiShippingFactory;
use SecureTrading\Trust\Model\SubscriptionFactory;
use SecureTrading\Trust\Helper\SubscriptionHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SecureTrading\Trust\Model\Source\Unit;
use SecureTrading\Trust\Model\Source\Type;

/**
 * Class Response
 *
 * @package SecureTrading\Trust\Controller\PaymentPage
 */
class Response extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var Session
	 */
	protected $checkoutSession;

	/**
	 * @var CommandPoolInterface
	 */
	protected $commandPool;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var PaymentTokenFactoryInterface
	 */
	protected $paymentTokenFactory;

	/**
	 * @var OrderPaymentExtensionInterfaceFactory
	 */
	protected $extensionInterfaceFactory;

	/**
	 * @var JWT
	 */
	protected $jwt;

	/**
	 * @var Json
	 */
	protected $json;

	/**
	 * @var SubscriptionFactory
	 */
	protected $subscriptionFactory;

	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;

	/**
	 * @var array
	 */
	protected $subscriptionData = [
		'orderreference'          => 'order_id',
		'subscriptionunit'        => 'unit',
		'subscriptionfrequency'   => 'frequency',
		'subscriptionfinalnumber' => 'final_number',
		'subscriptiontype'        => 'type',
	];

	/**
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * @var OrderSender
	 */
	protected $orderSender;

	/**
	 * Response constructor.
	 *
	 * @param Context $context
	 * @param ConfigInterface $config
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param Session $checkoutSession
	 * @param CommandPoolInterface $commandPool
	 * @param PaymentTokenFactoryInterface $paymentTokenFactory
	 * @param OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory
	 * @param Logger $logger
	 * @param JWT $jwt
	 * @param Json $json
	 * @param SubscriptionFactory $subscriptionFactory
	 * @param SubscriptionHelper $subscriptionHelper
	 * @param MultiShippingFactory $multiShippingFactory
	 * @param SerializerInterface $serializer
	 * @param OrderSender $orderSender
	 */
	function __construct(
		Context $context,
		ConfigInterface $config,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		Session $checkoutSession,
		CommandPoolInterface $commandPool,
		PaymentTokenFactoryInterface $paymentTokenFactory,
		OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory,
		Logger $logger,
		JWT $jwt,
		Json $json,
		SubscriptionFactory $subscriptionFactory,
		SubscriptionHelper $subscriptionHelper,
		MultiShippingFactory $multiShippingFactory,
		SerializerInterface $serializer,
		OrderSender $orderSender
	) {
		$this->checkoutSession = $checkoutSession;
		$this->orderFactory    = $orderFactory;
		$this->config          = $config;
		$this->commandPool     = $commandPool;
		$this->paymentTokenFactory = $paymentTokenFactory;
		$this->extensionInterfaceFactory = $extensionInterfaceFactory;
		$this->logger          = $logger;
		$this->jwt             = $jwt;
		$this->json            = $json;
		$this->subscriptionFactory = $subscriptionFactory;
		$this->subscriptionHelper  = $subscriptionHelper;
		$this->multiShippingFactory = $multiShippingFactory;
		$this->serializer          = $serializer;
		$this->orderSender         = $orderSender;
		parent::__construct($context);
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	function execute()
	{
		$this->logger->debug('--- Execute Response ---');
		# 2022-03-23 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# The previous code:
		#	$isUsedIframe = 0;
		$isUsedIframe = 1;
		try {
			$responseParams = $this->getRequest()->getParams();
			# 2022-03-23 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# The previous code:
			#	if (isset($responseParams['isusediframe'])) {
			#		$isUsedIframe = $responseParams['isusediframe'];
			#	}
			if ($responseParams['paymenttypedescription'] == 'ZIP') {
				return $this->redirect($isUsedIframe, 'securetrading/apisecuretrading/ZipPaymentReturnUrl', $responseParams);
			}
			$multiShippingSetId = isset($responseParams['multishippingsetid']) ? $responseParams['multishippingsetid'] : null;
			$isMultiShipping    = isset($responseParams['ismultishipping']) ? $responseParams['ismultishipping'] : 0;
			if (!empty($responseParams)) {
				$this->logger->debug('--- Response Params: ', array($responseParams));
				$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
				/** @var Order $order */
				$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

				if (empty($order->getId())) {
					$this->messageManager->addError(__("Something went wrong. Please try again later."));
					return $this->redirect($isUsedIframe, 'checkout/onepage/failure');
				}
				if ($this->isValid($responseParams)) {
					$this->logger->debug('--- Response Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
					if ($this->getRequest()->getParam('errorcode', null) === "0") {
						if($isMultiShipping == 1 && $multiShippingSetId != null){
							$this->restoredMultiShippingCheckoutSession($multiShippingSetId);
							$redirectPath = 'multishipping/checkout/success';
						} else {
							$this->restoredCheckoutSession($order);
							$redirectPath = 'checkout/onepage/success';
						}
						$this->logger->debug('--- Restored Checkout Session Successfully---');
						return $this->redirect($isUsedIframe, $redirectPath);

					} else {
						$order->cancel();
						$order->save();
						return $this->redirect($isUsedIframe, 'checkout/cart');
					}
				}
			}
		} catch (\Exception $exception) {
			$this->logger->debug('--- Notification Response Error Msg: ' . $exception->getMessage() . '---');
			$this->messageManager->addError(__($exception->getMessage()));
			return $this->redirect($isUsedIframe, 'checkout/onepage/failure');
		}
		return $this->redirect($isUsedIframe, 'checkout/onepage/failure');
	}

	/**
	 * @param Order $order
	 */
	function restoredCheckoutSession(Order $order)
	{
		$this->checkoutSession->setLastOrderId($order->getId());
		$this->checkoutSession->setLastRealOrderId($order->getIncrementId());
		$this->checkoutSession->setLastQuoteId($order->getQuoteId());
		$this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
	}

	function redirect($isUsedIframe, $redirectPath, $params = []) {
		if ($isUsedIframe == 1) {
			return $this->resultRedirectFactory->create()->setPath('securetrading/paymentpage/redirect?redirect_path='.urlencode($redirectPath), $params);
		} else {
			return $this->resultRedirectFactory->create()->setPath($redirectPath, $params);
		}
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	protected function isValid(array $data) {
		# 2022-03-23 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# «Site security» is not enabled on the ableskills.co.uk website,
		# so Trust Payments does not return the `responsesitesecurity` parameter.
		# https://help.trustpayments.com/hc/en-us/articles/4402734345361
		return true;
		$string = '';
		if (empty($data['responsesitesecurity'])) {
			return false;
		}

		$responseSiteSecurity = $data['responsesitesecurity'];
		unset($data['responsesitesecurity']);
		unset($data['notificationreference']);

		ksort($data);
		array_push($data, $this->config->getValue(Data::SITE_PASS));

		foreach ($data as $key => $value) {
			$string .= $value;
		}

		$hash = hash("sha256", $string);

		if ($responseSiteSecurity == $hash) {
			return true;
		}

		return false;
	}

	/**
	 * @param $payment
	 * @param $responseParams
	 * @throws \Exception
	 */
	protected function processSubscription($payment, $responseParams)
	{
		$data = [];
		if (isset($responseParams['issubscription']) && $responseParams['issubscription'] == 1) {
			$paymentAdditionalInformation = $payment->getAdditionalInformation('secure_trading_data');
			if (isset($paymentAdditionalInformation)) {
				foreach ($paymentAdditionalInformation as $key => $value) {
					if (isset($this->subscriptionData[$key])) {
						$data[$this->subscriptionData[$key]] = $value;
					}
				}
			}
			if (isset($responseParams['transactionreference'])) {
				$data['transaction_id'] = $responseParams['transactionreference'];
				$data['parent_transaction_id'] = $responseParams['transactionreference'];
			}
			if(isset($responseParams['orderreference'])){
				$data['parent_order_id'] = $responseParams['orderreference'];
			}
			$data['type'] = $this->convertType($data['type']);
			$data['unit'] = $this->convertUnit($data['unit']);
			$data['skip_the_first_payment'] = isset($responseParams['skipthefirstpayment'])? $responseParams['skipthefirstpayment'] : 0;
			$data['status'] = 0;
			$data['number'] = 1;
			$this->logger->debug('--- Data to save subscription: ', array($data));
			$subscription   = $this->subscriptionFactory->create();
			if( empty($subscription->load($responseParams['transactionreference'],'transaction_id')->getData()))
			{
				$subscription->setData($data);
				$subscription->save();
			}
		}
	}

	/**
	 * @param $multiShippingSetId
	 */
	protected function restoredMultiShippingCheckoutSession($multiShippingSetId)
	{
		$multiShipping = $this->multiShippingFactory->create()->load($multiShippingSetId);
		if($multiShipping->getListOrders()){
			$this->checkoutSession->setOrderIds($multiShipping->getListOrders());
		}
	}

	/**
	 * @param $multiShippingSetId
	 * @param $responseParams
	 */
	protected function processMultiShipping($multiShippingSetId, $responseParams){
		$multiShipping = $this->multiShippingFactory->create()->load($multiShippingSetId);
		if ($orderIds = $multiShipping->getListOrders()) {
			try{
			$this->logger->debug("---- MultiShipping List Order Ids: ". $orderIds);
			$orderIds = $this->serializer->unserialize($orderIds);
			foreach($orderIds as $id => $incrementId){
				$order = $this->orderFactory->create()->load($id);
				if($order->getId()){
					/** @var Order\Payment $payment */
					$payment = $order->getPayment();
					if(empty($payment->getAdditionalInformation('transactionreference'))){
						foreach ($responseParams as $key => $value) {
							if($key == 'rules'){
								$value = $this->json->serialize($value);
							}
							$payment->setAdditionalInformation($key, $value);
						}
						if($payment->getMethod() === "api_secure_trading"){
							$paymentAction = $this->scopeConfig->getValue('payment/api_secure_trading/api_payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
						}elseif ($payment->getMethod() === "secure_trading"){
							$paymentAction = $payment->getAdditionalInformation('payment_action');
						}

						if (empty($payment->getAdditionalInformation('payment_action'))){
							$payment->setAdditionalInformation('payment_action', $paymentAction);
						}

						if ($paymentAction == 'authorize') {
							$this->commandPool->get('authorize')->execute(['order' => $order, 'info' => $responseParams]);
						} elseif ($paymentAction == 'authorize_capture') {
							$this->commandPool->get('authorize_capture')->execute(['order' => $order, 'info' => $responseParams]);
						}
						$this->sendEmailAfterPayment($order);
					}
				}
			}
			} catch(\Exception $e){

			}
		}
	}

	/**
	 * @param $multiShippingSetId
	 * @param $responseParams
	 * @throws \Exception
	 */
	protected function declineMultiShipping($multiShippingSetId, $responseParams){
		$multiShipping = $this->multiShippingFactory->create()->load($multiShippingSetId);
		if ($orderIds = $multiShipping->getListOrders()) {
			$orderIds = $this->serializer->unserialize($orderIds);
			foreach ($orderIds as $id => $incrementId) {
				$order = $this->orderFactory->create()->load($id);
				$order->addCommentToStatusHistory(__('Transaction has been declined. Request reference: %1', $responseParams['requestreference']));
				$order->save();
			}
		}
	}

	/**
	 * @param $order
	 */
	protected function sendEmailAfterPayment($order){
		$this->orderSender->send($order);
	}

	//New code

	/**
	 * @param $response
	 * @param $payment
	 * @param $order
	 * @return |null
	 */
	protected function saveCardInfotoVault($response, $payment, $order)
	{
		if ($order->getCustomerIsGuest() == 0)
		{
			$cardDigits = $response['maskedpan'];
			$accountType = $response['accounttypedescription'];
			$paymentType = $this->getPaymentType($response['paymenttypedescription']);
			$parentTrans = $response['transactionreference'];
			$publichash = $this->genPublicHash($response['transactionreference']);
			$expireTime = $this->getExpireTime($order->getCreatedAt());
			$customerId = $order->getCustomerId();
			$detail = $this->json->serialize([
				"maskedpan"     => substr($cardDigits,-4),
				"cardExpire"    => $this->getCardExpire($response['expirydate']),
				"accountType"   => $accountType,
				"paymentType"   => $paymentType,
				"payment_action"=> $payment->getAdditionalInformation('payment_action'),
				"parenttransactionreference" => $parentTrans
			]);
			$paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

			if (!isset($response['transactionreference']) || empty($response['transactionreference']))
			{
				return null;
			}

			$paymentToken->setCustomerId($customerId);
			$paymentToken->setGatewayToken($parentTrans);
			$paymentToken->setTokenDetails($detail);
			$paymentToken->setExpiresAt($expireTime);
			$paymentToken->setPaymentMethodCode($payment->getMethod());
			$paymentToken->setPublicHash($publichash);
			$paymentToken->save();
		}
	}

	/**
	 * @param $time
	 * @return false|int|string
	 */
	private function getExpireTime($time)
	{
		$expireTime = strtotime($time);
		$expireTime = date('Y-m-d H:i:s',(int)$expireTime + 2592000);
		return $expireTime;
	}

	/**
	 * @param $paymentTypeDescription
	 * @return mixed
	 */
	private function getPaymentType($paymentTypeDescription)
	{
		$listType = [
			'VISA'       => 'VI',
			'MASTERCARD' => 'MC',
		];

		key_exists($paymentTypeDescription,$listType) ?
			$paymentType = $listType[$paymentTypeDescription] : $paymentType = $paymentTypeDescription;

		return $paymentType;
	}

	/**
	 * @param $cardExpire
	 * @return array
	 */
	private function getCardExpire($cardExpire)
	{
		return isset($cardExpire) ? array(explode("/",$cardExpire)) : array();
	}

	/**
	 * @param $parentTransaction
	 * @return string
	 */
	private function genPublicHash($parentTransaction)
	{
		return base64_encode($parentTransaction);
	}

	/**
	 * @param $unit
	 * @return false|int|string
	 */
	protected function convertUnit($unit)
	{
		return array_search($unit,Unit::getOptionArray());
	}

	/**
	 * @param $type
	 * @return false|int|string
	 */
	protected function convertType($type)
	{
		return array_search($type, Type::getOptionArray());
	}

}