<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
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
use phpDocumentor\Reflection\Types\Self_;
use SecureTrading\Trust\Controller\PaymentPage\Response;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Gateway\Config\Config;
use SecureTrading\Trust\Helper\SubscriptionHelper;
use SecureTrading\Trust\Model\MultiShippingFactory;
use SecureTrading\Trust\Model\SubscriptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class GetUrlRedirectPayPal extends Response
{
	protected $add = [
		'transactionreference',
		'paymenttypedescription',
		'transactionstartedtimestamp',
		'accounttypedescription',
		'redirecturl',
		'requesttypedescription',
		'paypaltoken'
	];
	protected $jsonFactory;

	protected $orderFactory;

	protected $jwt;

	protected $logger;

	protected $config;

	protected $scopeConfig;

	public function __construct(Context $context, ConfigInterface $config, \Magento\Sales\Model\OrderFactory $orderFactory, Session $checkoutSession, CommandPoolInterface $commandPool, PaymentTokenFactoryInterface $paymentTokenFactory, OrderPaymentExtensionInterfaceFactory $extensionInterfaceFactory, Logger $logger, JWT $jwt, Json $json, SubscriptionFactory $subscriptionFactory, SubscriptionHelper $subscriptionHelper, MultiShippingFactory $multiShippingFactory, SerializerInterface $serializer, OrderSender $orderSender, ScopeConfigInterface $scopeConfig, JsonFactory $jsonFactory )
	{
		parent::__construct($context, $config, $orderFactory, $checkoutSession, $commandPool, $paymentTokenFactory, $extensionInterfaceFactory, $logger, $jwt, $json, $subscriptionFactory, $subscriptionHelper, $multiShippingFactory, $serializer, $orderSender);
		$this->scopeConfig = $scopeConfig;
		$this->jsonFactory = $jsonFactory;
	}

	public function execute()
	{
		$this->logger->debug('--- Prepare data to redirect PayPal Page---');
		try {
			$data = $this->getRequest()->getParams();
			$orderId = $this->getRequest()->getParam('orderId');
			$this->logger->debug('--- Redirect PayPal Page Order ID ', array($orderId));
			$order = $this->orderFactory->create()->load($orderId);
			$information = $this->add;
			if (!empty($order->getId())) {
				$payment = $order->getPayment();
				isset($data['grandTotal']) ? $amount = $data['grandTotal'] : $amount = $order->getBaseGrandTotal();
				$data = $this->commandPool->get('get_link_redirect')->execute(['order' => $order, 'amount' => $amount]);
				if ($data['errorcode'] == 0) {
					foreach ($information as $key) {
						$payment->setAdditionalInformation($key, $data[$key]);
						$payment->save();
					}
					$resultJson = $this->jsonFactory->create();
					return $resultJson->setData($data);
				}
				return $this->_redirect('checkout/cart');
			} else {
				throw new \Magento\Framework\Exception\LocalizedException(__("Error"));
			}
		} catch (\Exception $e) {
			$this->logger->addDebug('API SecureTrading Error:' . $e->getMessage());
			throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
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
}
