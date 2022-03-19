<?php

namespace SecureTrading\Trust\Block\Adminhtml\ApiSecureTrading;

use Magento\Backend\Block\Template;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class Redirect
 * @package SecureTrading\Trust\Block\Adminhtml\ApiSecureTrading
 */
class Redirect extends Template
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
	 * Redirect constructor.
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
		$dataBuilder['orderId'] = $this->getRequest()->getParam('order_id');
		$dataBuilder['order'] = $this->orderFactory->create()->load($dataBuilder['orderId']);
		$dataBuilder['currencyiso3a'] = $this->subscriptionHelper->getCurrentCurrencyCode();
		$dataBuilder['sitereference'] = $this->subscriptionHelper->getSitereference();
		$dataBuilder['accounttypedescription'] = $this->subscriptionHelper->getAccountTypeDescription();
		$dataBuilder['grandTotal'] = $dataBuilder['order']->getGrandTotal();
		$dataBuilder['generateJwt'] = $this->storeManager->getStore()->getBaseUrl().'securetrading/apisecuretrading/generatejwt';
		$dataBuilder['cardUrl'] = $this->urlBuilder->getUrl('securetrading/apisecuretrading/cardurl');
		$dataBuilder['animated_card'] = $this->subscriptionHelper->getAnimatedCard();
		$dataBuilder['accountcheck'] = $this->storeManager->getStore()->getConfig(Data::ACCOUNT_CHECK);
		$dataBuilder['animated_card'] = $this->storeManager->getStore()->getConfig(Data::ANIMATED_CARD);
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

	/**
	 * @return bool
	 */
	public function isRedirectedToOrderGrid()
	{
		if (!empty($this->getRequest()->getParam('redirect_path'))) {
			return true;
		}
		return false;
	}
}