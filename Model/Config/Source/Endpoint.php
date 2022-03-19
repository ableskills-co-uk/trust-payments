<?php

namespace SecureTrading\Trust\Model\Config\Source;

/**
 * Class Endpoint
 *
 * @package SecureTrading\Trust\Model\Config\Source
 */
class Endpoint implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return [['value' => 'https://payments.securetrading.net/', 'label' => 'EU'],
				['value' => 'https://payments.securetrading.us/', 'label' => 'US']];
	}
}