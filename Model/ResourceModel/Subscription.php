<?php

namespace SecureTrading\Trust\Model\ResourceModel;

/**
 * Class Subscription
 *
 * @package SecureTrading\Trust\Model\ResourceModel
 */
class Subscription extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	/**
	 *
	 */
	public function _construct()
	{
		$this->_init('secure_trading_subscription',
			'id');
	}
}