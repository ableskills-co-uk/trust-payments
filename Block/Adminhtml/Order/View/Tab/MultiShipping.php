<?php

namespace SecureTrading\Trust\Block\Adminhtml\Order\View\Tab;

use SecureTrading\Trust\Model\MultiShippingFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class MultiShipping
 *
 * @package SecureTrading\Trust\Block\Adminhtml\Order\View\Tab
 */
class MultiShipping extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
	/**
	 * @var string
	 */
	protected $_template = 'SecureTrading_Trust::order/view/tab/multishipping.phtml';

	/**
	 * @var \Magento\Framework\Registry
	 */
	protected $_coreRegistry;

	/**
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
	protected $coreConfig;

	/**
	 * MultiShipping constructor.
	 *
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param MultiShippingFactory $multiShippingFactory
	 * @param SerializerInterface $serializer
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Registry $registry,
		MultiShippingFactory $multiShippingFactory,
		SerializerInterface $serializer,
		\Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
		array $data = []
	) {
		$this->multiShippingFactory = $multiShippingFactory;
		$this->serializer           = $serializer;
		$this->_coreRegistry        = $registry;
		$this->coreConfig           = $coreConfig;
		parent::__construct($context, $data);
	}

	/**
	 * @return mixed
	 */
	public function getOrder()
	{
		return $this->_coreRegistry->registry('current_order');
	}

	/**
	 * @return mixed
	 */
	public function getOrderId()
	{
		return $this->getOrder()->getEntityId();
	}

	/**
	 * @return mixed
	 */
	public function getOrderIncrementId()
	{
		return $this->getOrder()->getIncrementId();
	}

	/**
	 * @return \Magento\Framework\Phrase|string
	 */
	public function getTabLabel()
	{
		return __('Multishipping Related Orders');
	}

	/**
	 * @return \Magento\Framework\Phrase|string
	 */
	public function getTabTitle()
	{
		return __('Multishipping Related Orders');
	}

	/**
	 * @return bool
	 */
	public function canShowTab()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isHidden()
	{
		if ($this->coreConfig->getValue('multishipping/options/checkout_multiple')) {
			$setId = $this->getSetId();
			if (!empty($setId)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array|bool|float|int|string|null
	 */
	public function getRelatedOrders()
	{
		$setId         = $this->getSetId();
		$multiShipping = $this->multiShippingFactory->create()->load($setId);
		if ($multiShipping->getSetId()) {
			$listOrders = $multiShipping->getListOrders();
			return $this->serializer->unserialize($listOrders);
		}
		return [];
	}

	/**
	 * @return mixed
	 */
	public function getSetId()
	{
		$payment = $this->getOrder()->getPayment();
		$setId   = $payment->getAdditionalInformation('multishipping_set_id');
		return $setId;
	}

	/**
	 * @param $id
	 * @return string
	 */
	public function buildUrl($id)
	{
		return $this->escapeHtml($this->getUrl('sales/order/view', ['order_id' => $id]));
	}
}