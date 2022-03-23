<?php

namespace SecureTrading\Trust\Block\Account;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class STSortLink
 *
 * @package SecureTrading\Trust\Block\Account
 */
class STSortLink extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
{
	/**
	 * @var ProductMetadata
	 */
	protected $productMetaData;

	/**
	 * STSortLink constructor.
	 *
	 * @param ProductMetadata $productMetaData
	 * @param Context $context
	 * @param DefaultPathInterface $defaultPath
	 * @param array $data
	 */
	function __construct(ProductMetadata $productMetaData, Context $context, DefaultPathInterface $defaultPath, array $data = [])
	{
		$this->productMetaData = $productMetaData;
		parent::__construct($context, $defaultPath, $data);
	}

	/**
	 * @return int|mixed
	 */
	function getSortOrder()
	{
		return $this->getData(self::SORT_ORDER);
	}

	/**
	 * @return string
	 */
	function _toHtml()
	{
		if (version_compare($this->productMetaData->getVersion(), "2.3.3") < 0)
			return "";
		else
			return parent::_toHtml();
	}
}