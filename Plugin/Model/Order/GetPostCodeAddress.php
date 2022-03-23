<?php

namespace SecureTrading\Trust\Plugin\Model\Order;

/**
 * Class GetPostCodeAddress
 *
 * @package SecureTrading\Trust\Plugin\Model\Order
 */
class GetPostCodeAddress
{
	/**
	 * @param $subject
	 * @param $result
	 * @return string
	 */
	function afterGetPostcode($subject, $result)
	{
		if ($result == null)
			return '';
		return $result;
	}
}