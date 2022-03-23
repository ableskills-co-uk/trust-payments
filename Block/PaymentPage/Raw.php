<?php

namespace SecureTrading\Trust\Block\PaymentPage;

use SecureTrading\Trust\Helper\Data;

/**
 * Class Raw
 *
 * @package SecureTrading\Trust\Block\PaymentPage
 */
class Raw extends Iframe
{
	/**
	 * @return mixed
	 */
	function getRedirectUrl()
	{
		if (!$this->config->getValue(Data::SKIP_CHOICE_PAGE)) {
			return $this->config->getValue(Data::CHOICE_PAGE);
		}
		return $this->config->getValue(Data::DETAILS_PAGE);
	}

	/**
	 * @return array
	 */
	function getRedirectData()
	{
		$orderId       = $this->getRequest()->getParam('orderId', null);
		$multiShipping = $this->getRequest()->getParam('multishipping', null);
		$order         = $this->orderFactory->create()->load($orderId);
		$info          = [];
		if ($order->getId()) {
			$payment            = $order->getPayment();
			$multiShippingData  = $payment->getAdditionalInformation('multishipping_data');
			$multiShippingSetId = $payment->getAdditionalInformation('multishipping_set_id');
			if ($multiShipping == 1 && !empty($multiShippingData) && !empty($multiShippingSetId)) {
				$info = $multiShippingData;
			} else {
				$info = $payment->getAdditionalInformation('secure_trading_data');
			}
		}
		return (array)$info;
	}
}