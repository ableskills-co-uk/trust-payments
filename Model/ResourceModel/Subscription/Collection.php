<?php

namespace SecureTrading\Trust\Model\ResourceModel\Subscription;
/**
 * Subscription Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	function _construct()
	{
		$this->_init('SecureTrading\Trust\Model\Subscription',
			'SecureTrading\Trust\Model\ResourceModel\Subscription');
	}
}
