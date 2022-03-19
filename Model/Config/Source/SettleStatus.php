<?php

namespace SecureTrading\Trust\Model\Config\Source;

/**
 * Class SettleStatus
 *
 * @package SecureTrading\Trust\Model\Config\Source
 */
class SettleStatus implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return [
			['value' => 0, 'label' => '0 - Pending Settlement'],
			['value' => 1, 'label' => '1 - Pending Settlement (Manually Overridden)']
		];
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	public static function getSettleStatusByCode($code)
	{
		$array = array(
			'0'   => '0 - Pending Settlement',
			'1'   => '1 - Pending Settlement (Manually Overridden)',
			'2'   => '2 - Suspended',
			'3'   => '3 - Cancelled',
			'100' => '100 - Settled (Only available for certain aquirers)'
		);

		return isset($array[$code]) ? $array[$code] : $code;
	}
}
