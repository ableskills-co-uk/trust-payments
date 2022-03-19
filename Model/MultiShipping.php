<?php

namespace SecureTrading\Trust\Model;

/**
 * Class MultiShipping
 *
 * @package SecureTrading\Trust\Model
 */
class MultiShipping extends \Magento\Framework\Model\AbstractModel
{
	/**
	 *
	 */
	public function _construct()
	{
		$this->_init
		('SecureTrading\Trust\Model\ResourceModel\MultiShipping');
	}
}