<?php

namespace SecureTrading\Trust\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class SkipTheFirstPayment
 *
 * @package SecureTrading\Trust\Model\Source
 */
class SkipTheFirstPayment extends AbstractSource implements SourceInterface, OptionSourceInterface
{
	/**
	 * @return array
	 */
	function getAllOptions()
	{
		$result = [];

		foreach (self::getOptionArray() as $index => $value) {
			$result[] = ['value' => $index, 'label' => $value];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getOptionArray()
	{
		return
			[
				0 => __('No'),
				1 => __('Yes'),
			];
	}
}