<?php

namespace SecureTrading\Trust\Model\ResourceModel\Subscription\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Registry;

/**
 * Class Collection
 *
 * @package SecureTrading\Trust\Model\ResourceModel\Subscription\Grid
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
	/**
	 * @var Registry
	 */
	protected $coreRegistry;

	/**
	 * Collection constructor.
	 *
	 * @param Registry $registry
	 * @param EntityFactory $entityFactory
	 * @param Logger $logger
	 * @param FetchStrategy $fetchStrategy
	 * @param EventManager $eventManager
	 * @param string $mainTable
	 * @param string $resourceModel
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function __construct(
		Registry $registry,
		EntityFactory $entityFactory,
		Logger $logger,
		FetchStrategy $fetchStrategy,
		EventManager $eventManager,
		$mainTable = 'secure_trading_subscription',
		$resourceModel = \Magento\Sales\Model\ResourceModel\Order::class
	) {
		$this->coreRegistry = $registry;
		parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
	}

	/**
	 * @return $this|\Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult|void
	 */
	public function _initSelect()
	{
		parent::_initSelect();
		$this->addFieldToFilter('number', 1);
		return $this;
	}
}