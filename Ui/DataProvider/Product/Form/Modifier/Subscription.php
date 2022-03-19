<?php

namespace SecureTrading\Trust\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class Subscription
 *
 * @package SecureTrading\Trust\Ui\DataProvider\Product\Form\Modifier
 */
class  Subscription extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
	/**
	 * @var LocatorInterface
	 */
	protected $locator;

	/**
	 * @var \Magento\Framework\Serialize\Serializer\Json
	 */
	protected $json;

	/**
	 * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
	 */
	protected $typeConfigurable;

	/**
	 * Subscription constructor.
	 *
	 * @param LocatorInterface $locator
	 * @param \Magento\Framework\Serialize\Serializer\Json $json
	 * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $typeConfigurable
	 */
	public function __construct(LocatorInterface $locator,
								   \Magento\Framework\Serialize\Serializer\Json $json,
								   \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $typeConfigurable)
	{
		$this->locator = $locator;
		$this->json    = $json;
		$this->typeConfigurable = $typeConfigurable;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function modifyData(array $data)
	{
		$product = $this->locator->getProduct();
		$productId = $product->getId();
		$productData = $product->getData();
		$isEnableSecureTradingSubscription = isset($productData[Data::ATT_ENABLE_SUBS]) ? $productData[Data::ATT_ENABLE_SUBS] : 0;
		if($isEnableSecureTradingSubscription){
			$data[$productId]['product'][Data::ATT_ENABLE_SUBS] = $isEnableSecureTradingSubscription;
			$data[$productId]['product'][Data::ATT_REQUIRE_SUBS] = isset($productData[Data::ATT_REQUIRE_SUBS]) ? $productData[Data::ATT_REQUIRE_SUBS] : 0;
			$data[$productId]['product'][Data::ATT_OPTIONS_SUBS] = isset($productData[Data::ATT_OPTIONS_SUBS]) ? $this->json->unserialize($productData[Data::ATT_OPTIONS_SUBS]) : '';
		}
		$data[$productId]['product']['visible_subscription'] = true;
		if ($productId) {
            $parentsByChild = $this->typeConfigurable->getParentIdsByChild($productId);
            $parentProductId = array_shift($parentsByChild);
            if(isset($parentProductId)){
                $data[$productId]['product']['visible_subscription'] = false;
            }
        }
		if($product->getTypeId() == 'bundle' || $product->getTypeId() == 'grouped') {
			$data[$productId]['product']['visible_subscription'] = false;
		}
		return $data;
	}

	/**
	 * @param array $meta
	 * @return array
	 */
	public function modifyMeta(array $meta)
	{
		return $meta;
	}
}