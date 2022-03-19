<?php

namespace Securetrading\Trust\Plugin\Multishipping\Helper;

/**
 * Class ValidateMultiShipping
 *
 * @package Securetrading\Trust\Plugin\Multishipping\Helper
 */
class ValidateMultiShipping
{
	/**
	 * @param $subject
	 * @param $result
	 * @return bool
	 */
	public function afterIsMultishippingCheckoutAvailable($subject, $result)
	{
		$quote = $subject->getQuote();
		if ($quote) {
			$items = $quote->getAllVisibleItems();
			foreach ($items as $item) {
				$option = $item->getOptionByCode('secure_trading_subscription');
				if (isset($option)) {
					return false;
				}
			}
		}
		return $result;
	}
}