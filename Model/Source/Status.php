<?php

namespace SecureTrading\Trust\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 *
 * @package SecureTrading\Trust\Model\Source
 */
class Status extends AbstractSource implements SourceInterface, OptionSourceInterface
{
	/**
	 * @return array
	 */
	public function getAllOptions()
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
				0 => __('Processing'),
				1 => __('Completed'),
				2 => __('Cancelled'),
			];
	}
}