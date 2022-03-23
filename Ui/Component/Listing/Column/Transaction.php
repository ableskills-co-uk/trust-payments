<?php

namespace SecureTrading\Trust\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class Transaction
 *
 * @package SecureTrading\Trust\Ui\Component\Listing\Column
 */
class Transaction extends Column
{
	/**
	 * @var UrlInterface
	 */
	protected $urlBuilder;

	/**
	 * @param ContextInterface $context
	 * @param UiComponentFactory $uiComponentFactory
	 * @param UrlInterface $urlBuilder
	 * @param array $components
	 * @param array $data
	 */
	function __construct(
		ContextInterface $context,
		UiComponentFactory $uiComponentFactory,
		UrlInterface $urlBuilder,
		array $components = [],
		array $data = []
	) {
		$this->urlBuilder = $urlBuilder;
		parent::__construct($context, $uiComponentFactory, $components, $data);
	}

	/**
	 * Prepare Data Source
	 *
	 * @param array $dataSource
	 * @return array
	 */
	function prepareDataSource(array $dataSource)
	{
		if (isset($dataSource['data']['items'])) {
			foreach ($dataSource['data']['items'] as &$item) {
				$item[$this->getData('name')] = "<a target='_blank' href='https://myst.securetrading.net/transactions/singletransaction?transactionreference={$item[$this->getData('name')]}'>{$item[$this->getData('name')]}</a>";
			}
		}

		return $dataSource;
	}
}