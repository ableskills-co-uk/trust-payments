<?php

namespace SecureTrading\Trust\Block\ApiSecureTrading;

use Magento\Backend\Block\Template;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class Form
 * @package SecureTrading\Trust\Block\ApiSecureTrading
 */
class Form extends Template
{
	/**
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var \Magento\Framework\Serialize\Serializer\Json
	 */
	protected $jsonDecode;

	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;

	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var \Magento\Framework\UrlInterface
	 */
	protected $urlBuilder;

	/**
	 * Form constructor.
	 * @param Template\Context $context
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param \Magento\Framework\Serialize\Serializer\Json $jsonDecode
	 * @param SubscriptionHelper $subscriptionHelper
	 * @param StoreManagerInterface $storeManager
	 * @param \Magento\Framework\UrlInterface $urlBuilder
	 * @param array $data
	 */
	public function __construct(
		Template\Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Serialize\Serializer\Json $jsonDecode,
		SubscriptionHelper $subscriptionHelper,
		StoreManagerInterface $storeManager,
		\Magento\Framework\UrlInterface $urlBuilder,
		array $data = [])
	{
		$this->orderFactory = $orderFactory;
		$this->jsonDecode   = $jsonDecode;
		$this->subscriptionHelper  = $subscriptionHelper;
		$this->storeManager = $storeManager;
		$this->urlBuilder = $urlBuilder;
		parent::__construct($context, $data);
	}

	/**
	 * @return array
	 */
	public function getOrderData()
	{
		$dataBuilder         = [];
		$dataBuilder['orderId'] = $this->getRequest()->getParam('orderId');
		$dataBuilder['order'] = $this->orderFactory->create()->load($dataBuilder['orderId']);

		$multiData = $dataBuilder['order']->getPayment()->getAdditionalInformation('multishipping_data');

		$dataBuilder['currencyiso3a'] = $this->subscriptionHelper->getCurrentCurrencyCode();
		$dataBuilder['animated_card'] = $this->subscriptionHelper->getAnimatedCard();
		$dataBuilder['sitereference'] = $this->subscriptionHelper->getSitereference();
		$dataBuilder['accounttypedescription'] = $this->subscriptionHelper->getAccountTypeDescription();
		$dataBuilder['grandTotal'] = $multiData['mainamount'];
		$dataBuilder['generateJwt'] = $this->urlBuilder->getUrl('securetrading/apisecuretrading/generatejwt');
		$dataBuilder['cardUrl'] = $this->urlBuilder->getUrl('securetrading/apisecuretrading/cardurl');
		$dataBuilder['accountcheck'] = $this->storeManager->getStore()->getConfig(Data::ACCOUNT_CHECK);
		$dataBuilder['animated_card'] = $this->storeManager->getStore()->getConfig(Data::ANIMATED_CARD);
		$dataBuilder['active_visa_checkout'] = $this->storeManager->getStore()->getConfig(Data::IS_VISACHECKOUT);
		$dataBuilder['merchant_id'] = $this->storeManager->getStore()->getConfig(Data::MERCHANT_ID);
		$dataBuilder['active_paypal_payment'] = $this->storeManager->getStore()->getConfig(Data::IS_PAYPALPAYMENT);
		$dataBuilder['paypal_merchant_id'] = $this->storeManager->getStore()->getConfig(Data::PAYPAL_MERCHANT_ID);
		$dataBuilder['name_site'] = $this->storeManager->getStore()->getConfig(Data::NAME_SITE);
		$dataBuilder['active_apple_pay'] = $this->storeManager->getStore()->getConfig(Data::IS_APPLE_PAY);
		$dataBuilder['apple_merchant_id'] = $this->storeManager->getStore()->getConfig(Data::APPLE_MERCHANT_ID);
		$dataBuilder['is_test'] = $this->storeManager->getStore()->getConfig(Data::IS_TEST_API);
		return $dataBuilder;
	}

	/**
	 * @param array $keys
	 * @return bool|false|string
	 */
	public function jsonDecode(array $keys = [])
	{
		return $this->jsonDecode->serialize($keys);
	}
}