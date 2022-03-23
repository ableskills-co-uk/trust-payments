<?php

namespace SecureTrading\Trust\Plugin\Catalog\Helper\Product;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class ShowSubscriptionOption
 *
 * @package SecureTrading\Trust\Plugin\Catalog\Helper\Product
 */
class ShowSubscriptionOption
{
	/**
	 * @var Json
	 */
	protected $serializer;

	/**
	 * ShowSubscriptionOption constructor.
	 *
	 * @param Json $json
	 */
	function __construct(Json $json) {
		$this->serializer = $json;
	}

	/**
	 * @param Configuration $subject
	 * @param $result
	 * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
	 * @return array
	 */
	function afterGetCustomOptions(
		Configuration $subject,
		$result,
		\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
	{
		$stSubsOptions = $item->getOptionByCode('secure_trading_subscription_detail');
		if($stSubsOptions){
			$result = array_merge($result, $this->serializer->unserialize($stSubsOptions->getValue()));
		}
		return $result;
	}
}