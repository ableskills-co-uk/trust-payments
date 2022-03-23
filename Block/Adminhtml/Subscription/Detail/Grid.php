<?php

namespace SecureTrading\Trust\Block\Adminhtml\Subscription\Detail;

/**
 * Class Grid
 *
 * @package SecureTrading\Trust\Block\Adminhtml\Subscription\Detail
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Registry|null
	 */
	protected $_coreRegistry = null;

	/**
	 * @var \SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory
	 */
	protected $_collectionFactory;

	/**
	 * @var array
	 */
	protected $_subscriptionColumns = [
		'order_id'       => 'Order ID',
		'transaction_id' => 'Transaction ID',
		'number'         => 'Subscription Number',
		'created_at'     => 'Created At',
	];

	/**
	 * Grid constructor.
	 *
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory $collectionFactory
	 * @param \Magento\Framework\Registry $coreRegistry
	 * @param array $data
	 */
	function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory $collectionFactory,
		\Magento\Framework\Registry $coreRegistry,
		array $data = []
	) {
		$this->_collectionFactory = $collectionFactory;
		$this->_coreRegistry      = $coreRegistry;
		parent::__construct($context, $backendHelper, $data);
	}

	/**
	 * Initialize default sorting and html ID
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->setId('subscriptionDetailsGrid');
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(false);
	}

	/**
	 * Prepare collection for grid
	 *
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$poid = $this->_coreRegistry->registry('poid');

		$collection = $this->_collectionFactory->create()->addFieldToFilter('parent_order_id', $poid);

		$this->setCollection($collection->load());

		parent::_prepareCollection();
		return $this;
	}

	/**
	 * Add columns to grid
	 *
	 * @return $this
	 */
	protected function _prepareColumns()
	{
		$renderer = '';
		foreach ($this->_subscriptionColumns as $key => $value) {
			if ($key == "transaction_id") $renderer = 'SecureTrading\Trust\Block\Adminhtml\Subscription\Detail\Renderer\Transaction';
			$this->addColumn(
				$key,
				[
					'header'   => __($value),
					'index'    => $key,
					'sortable' => false,
					'type'     => 'text',
					'escape'   => true,
					'renderer' => $renderer
				]
			);
			$renderer = '';
		}
		parent::_prepareColumns();
		return $this;
	}
}
