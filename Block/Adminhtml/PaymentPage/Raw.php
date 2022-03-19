<?php

namespace SecureTrading\Trust\Block\Adminhtml\PaymentPage;

use SecureTrading\Trust\Helper\Data;

/**
 * Class Raw
 *
 * @package SecureTrading\Trust\Block\Adminhtml\PaymentPage
 */
class Raw extends Redirect
{
	/**
	 * @return mixed
	 */
	public function getRedirectUrl()
	{
		if (!$this->config->getValue(Data::SKIP_CHOICE_PAGE)) {
			return $this->config->getValue(Data::CHOICE_PAGE);
		}
		return $this->config->getValue(Data::DETAILS_PAGE);
	}
}