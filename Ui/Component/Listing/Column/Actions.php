<?php

namespace SecureTrading\Trust\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class Actions
 *
 * @package SecureTrading\Trust\Ui\Component\Listing\Column
 */
class Actions extends Column
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
	public function __construct(
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
	public function prepareDataSource(array $dataSource)
	{
		if (isset($dataSource['data']['items'])) {
			foreach ($dataSource['data']['items'] as &$item) {
				$item[$this->getData('name')] = [
					'view'  =>[
						'href'   => $this->urlBuilder->getUrl(
							'securetrading/subscription/detail',
							['poid' => $item['order_id']]
						),
						'label'  => __('View'),
						'hidden' => false,
					],
					'cancel' =>[
						'href'   => $this->urlBuilder->getUrl(
							'securetrading/subscription/cancel',
							['subs_id' => $item['id']]
						),
						'label'  => __('Cancel'),
						'hidden' => false,
						'confirm' => [
							'title' => __('Cancel subscription'),
							'message' => __('Are you sure you want to cancel a subscription %1?', $item['id']),
						],
					],
				];
			}
		}

		return $dataSource;
	}
}
