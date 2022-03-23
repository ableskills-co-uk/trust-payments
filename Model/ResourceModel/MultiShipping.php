<?php

namespace SecureTrading\Trust\Model\ResourceModel;

/**
 * Class MultiShipping
 *
 * @package SecureTrading\Trust\Model\ResourceModel
 */
class MultiShipping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	/**
	 *
	 */
	function _construct()
	{
		$this->_init('secure_trading_multishipping',
			'set_id');
	}
}