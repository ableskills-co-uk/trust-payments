<?php

namespace SecureTrading\Trust\Model;

/**
 * Class Subscription
 *
 * @package SecureTrading\Trust\Model
 */
class Subscription extends \Magento\Framework\Model\AbstractModel
{
	/**
	 *
	 */
	public function _construct()
	{
		$this->_init
		('SecureTrading\Trust\Model\ResourceModel\Subscription');
	}

	/**
	 * @param string $transactionId
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function loadByTransactionId(string $transactionId)
	{
		$this->_getResource()->load($this, $transactionId, 'transaction_id');
		return $this;
	}
}