<?php

namespace SecureTrading\Trust\Observer\Payment;

use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Model\MultiShippingFactory;

/**
 * Class AbstractOperationObserver
 *
 * @package SecureTrading\Trust\Observer\Payment
 */
abstract class AbstractOperationObserver
{
	/**
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * @var CollectionFactory
	 */
	protected $collectionFactory;

	/**
	 * @var Registry
	 */
	protected $coreRegistry;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * AbstractOperationObserver constructor.
	 *
	 * @param MultiShippingFactory $multiShippingFactory
	 * @param CollectionFactory $collectionFactory
	 * @param SerializerInterface $serializer
	 * @param Registry $coreRegistry
	 * @param Logger $logger
	 */
	public function __construct(MultiShippingFactory $multiShippingFactory,
								CollectionFactory $collectionFactory,
								SerializerInterface $serializer,
								Registry $coreRegistry,
								Logger $logger)
	{
		$this->collectionFactory    = $collectionFactory;
		$this->serializer           = $serializer;
		$this->multiShippingFactory = $multiShippingFactory;
		$this->coreRegistry         = $coreRegistry;
		$this->logger               = $logger;
	}
}