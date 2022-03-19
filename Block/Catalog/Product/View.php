<?php

namespace SecureTrading\Trust\Block\Catalog\Product;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class View
 *
 * @package SecureTrading\Trust\Block\Catalog\Product
 */
class View extends Template
{
	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var ProductFactory
	 */
	protected $productFactory;

	/**
	 * @var Json
	 */
	protected $json;

	/**
	 * @var Cart
	 */
	protected $cart;

	/**
	 * @var SubscriptionHelper
	 */
	protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * View constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \SecureTrading\Trust\Helper\SubscriptionHelper $helper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
	    ScopeConfigInterface $scopeConfig,
	    ConfigInterface $config,
        RequestInterface $requestInterface,
        ProductFactory $productFactory,
        Json $json,
        Cart $cart,
        SubscriptionHelper $helper,
        Template\Context $context,
        array $data = []
    ) {
	    $this->scopeConfig    = $scopeConfig;
		$this->request        = $requestInterface;
		$this->config         = $config;
		$this->productFactory = $productFactory;
		$this->json           = $json;
		$this->cart           = $cart;
		$this->helper         = $helper;
		parent::__construct($context, $data);
	}

	/**
	 * @return mixed
	 */
	public function isActive()
	{
        return $this->scopeConfig->isSetFlag('payment/secure_trading/active', ScopeInterface::SCOPE_STORE) || $this->scopeConfig->isSetFlag('payment/api_secure_trading/active', ScopeInterface::SCOPE_STORE);
	}

	/**
	 * @return \Magento\Catalog\Model\Product|null
	 */
	public function getProduct()
	{
		$productId = $this->request->getParam('id');
		$product   = $this->productFactory->create()->load($productId);
		if ($product) {
			return $product;
		}
		return null;
	}

	/**
	 * @param string $data
	 * @return array|bool|float|int|mixed|string|null
	 */
	public function decodeOptions(string $data)
	{
		if ($data == '') return false;
		return $this->json->unserialize($data);
	}

	/**
	 * @param array $data
	 * @return bool|false|string
	 */
	public function encodeOptions(array $data)
	{
		if (!is_array($data)) return false;
		return $this->json->serialize($data);
	}

	/**
	 * @param int $skipTheFirstPayment
	 * @param int $frequency
	 * @param $unit
	 * @param int $finalNumber
	 * @param $priceProduct
	 * @param $style
	 * @return string
	 */
	public function getDescription(int $skipTheFirstPayment, int $frequency, $unit, int $finalNumber, $priceProduct, $style){
		return $this->helper->getDescription($skipTheFirstPayment, $frequency, $unit, $finalNumber, $priceProduct, $style);
	}
}
