<?php

namespace SecureTrading\Trust\Block\Account;

use Magento\Framework\View\Element\Template;

/**
 * Class Subscription
 *
 * @package SecureTrading\Trust\Block\Account
 */
class Subscription extends Template
{
	/**
	 * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
	 */
	protected $orderCollectionFactory;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $customerSession;

	/**
	 * @var \SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory
	 */
	protected $subsCollectionFactory;

	/**
	 * @var \SecureTrading\Trust\Model\ResourceModel\Subscription\Grid\CollectionFactory
	 */
	protected $subsGridCollectionFactory;

	/**
	 * @var \Magento\Framework\Registry
	 */
	protected $registry;

	/**
	 * @var \SecureTrading\Trust\Helper\SubscriptionHelper
	 */
	protected $helper;

	/**
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	protected $orderFactory;

	/**
	 * Subscription constructor.
	 * @param Template\Context $context
	 * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory $subsCollectionFactory
	 * @param \SecureTrading\Trust\Model\ResourceModel\Subscription\Grid\CollectionFactory $subsGridCollectionFactory
	 * @param \Magento\Framework\Registry $registry
	 * @param \SecureTrading\Trust\Helper\SubscriptionHelper $helper
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param array $data
	 */
	function __construct(
		Template\Context $context,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Customer\Model\Session $customerSession,
		\SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory $subsCollectionFactory,
		\SecureTrading\Trust\Model\ResourceModel\Subscription\Grid\CollectionFactory $subsGridCollectionFactory,
		\Magento\Framework\Registry $registry,
		\SecureTrading\Trust\Helper\SubscriptionHelper $helper,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->orderCollectionFactory    = $orderCollectionFactory;
		$this->customerSession           = $customerSession;
		$this->subsCollectionFactory     = $subsCollectionFactory;
		$this->subsGridCollectionFactory = $subsGridCollectionFactory;
		$this->registry                  = $registry;
		$this->helper                    = $helper;
		$this->orderFactory               = $orderFactory;
	}

	/**
	 * @return $this|Template
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if ($this->customerSession->isLoggedIn()) {
			$orderId         = array();
			$customerId      = $this->customerSession->getCustomerId();
			$collectionOrder = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId);
			foreach ($collectionOrder as $value) {
				array_push($orderId, $value->getIncrementId());
			}
			$collectionSubs = $this->subsGridCollectionFactory->create()->addFieldToFilter('order_id', array('in' => $orderId))->setOrder('order_id', 'DESC');;
			$parentId = $this->getParentId();
			if (!empty($parentId)) {
				$collectionSubs = $this->subsCollectionFactory->create()->addFieldToFilter('order_id', array('in' => $orderId))->addFieldToFilter('parent_order_id', $parentId)->setOrder('order_id', 'DESC');
			}
			$this->setCollection($collectionSubs);
		}
		if ($this->getCollection()) {
			$pager = $this->getLayout()->createBlock(
				'Magento\Theme\Block\Html\Pager',
				'notidication.record.pager'
			)->setCollection(
				$this->getCollection()
			);
			$this->setChild('pager', $pager);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	function getPagerHtml()
	{
		return $this->getChildHtml('pager');
	}

	/**
	 * @return mixed
	 */
	function getParentId()
	{
		$parentId = $this->registry->registry('order_id');
		return $parentId;
	}

	/**
	 * @param $skipTheFirstPayment
	 * @param $frequency
	 * @param $unit
	 * @param $finalNumber
	 * @param $style
	 * @param $orderId
	 * @return string
	 */
	function getDescriptionSubs($skipTheFirstPayment, $frequency, $unit, $finalNumber, $style, $orderId){
		$price = $this->getPriceGrandTotal($orderId);
		$unit = $unit ? 'Month' : 'Day';
		$style = $style ? 'INSTALLMENT' : 'RECURRING';
		return $this->helper->getDescription($skipTheFirstPayment, $frequency, $unit, $finalNumber, $price, $style);
	}

	/**
	 * @param $orderId
	 * @return float
	 */
	function getPriceGrandTotal($orderId){
		$order = $this->orderFactory->create()->loadByIncrementId($orderId);
		return $order->getBaseGrandTotal();
	}
}