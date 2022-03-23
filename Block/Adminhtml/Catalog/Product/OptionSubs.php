<?php


namespace SecureTrading\Trust\Block\Adminhtml\Catalog\Product;


use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use SecureTrading\Trust\Helper\SubscriptionHelper;

class OptionSubs extends Template
{
	protected $productFactory;
	protected $json;
	protected $helper;

	function __construct(Template\Context $context, ProductFactory $productFactory, Json $json, SubscriptionHelper $helper, array $data = [])
	{
		parent::__construct($context, $data);
		$this->productFactory = $productFactory;
		$this->json = $json;
		$this->helper = $helper;
	}

	function getOptionSubscription()
	{
		try {
			$id = $this->_request->getParam('id');
			$product = $this->productFactory->create()->load($id);
			$optionsRawData = $product->getData(\SecureTrading\Trust\Helper\Data::ATT_OPTIONS_SUBS);
			$enableSubs = $product->getData(\SecureTrading\Trust\Helper\Data::ATT_ENABLE_SUBS);
			$options = $this->decodeOptions(isset($optionsRawData) ? $optionsRawData : '');
			$priceProduct = $product->getPriceInfo()->getPrice('final_price')->getValue();

			return ['options' => $options, 'priceProduct' => $priceProduct, 'enable' => $enableSubs];
		}catch (\Exception $e){
			throw new LocalizedException(__('Some thing went wrong'));
		}
	}

	function decodeOptions(string $data)
	{
		if ($data == '') return false;
		return $this->json->unserialize($data);
	}

	function getDescription(int $skipTheFirstPayment, int $frequency, $unit, int $finalNumber, $priceProduct, $style){
		return $this->helper->getDescription($skipTheFirstPayment, $frequency, $unit, $finalNumber, $priceProduct, $style);
	}
}