<?php

namespace SecureTrading\Trust\Block\Adminhtml\Subscription\Detail\Renderer;

use Magento\Framework\DataObject;

/**
 * Class Transaction
 *
 * @package SecureTrading\Trust\Block\Adminhtml\Subscription\Detail\Renderer
 */
class Transaction extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
	/**
	 * @param DataObject $row
	 * @return string
	 */
	function render(DataObject $row)
	{
		$html = '<a title="Go to transaction detail" target="_blank" href="https://myst.securetrading.net/transactions/singletransaction?transactionreference=' . $row->getData($this->getColumn()->getIndex()) . '">' . $row->getData($this->getColumn()->getIndex()) . '</a>';
		return $html;
	}
}