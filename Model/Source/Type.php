<?php

namespace SecureTrading\Trust\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Type
 *
 * @package SecureTrading\Trust\Model\Source
 */
class Type extends AbstractSource implements SourceInterface, OptionSourceInterface
{
	/**
	 * @return array
	 */
	public function getAllOptions()
	{
		$result = [];

		foreach (self::getOptionArray() as $index => $value) {
			$result[] = ['value' => $index, 'label' => __($value)];
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
				0 => 'RECURRING',
				1 => 'INSTALLMENT'
			];
	}
}