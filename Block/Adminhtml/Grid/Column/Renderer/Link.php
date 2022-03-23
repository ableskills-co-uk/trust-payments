<?php

namespace SecureTrading\Trust\Block\Adminhtml\Grid\Column\Renderer;

/**
 * Class Link
 *
 * @package SecureTrading\Trust\Block\Adminhtml\Grid\Column\Renderer
 */
class Link extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
	/**
	 * @param \Magento\Framework\DataObject $row
	 * @return string
	 */
	function _getValue(\Magento\Framework\DataObject $row)
	{
		$secureTradingMethods = [
			'secure_trading'
		];
		if (in_array($row->getMethod(), $secureTradingMethods) && !empty($row->getData('txn_id')))
			return '<a title="Go to transaction detail" target="_blank" href="https://myst.securetrading.net/transactions/singletransaction?transactionreference=' . $row->getData('txn_id') . '">' . $row->getData('txn_id') . '</a>';
		else
			return parent::_getValue($row);
	}
}