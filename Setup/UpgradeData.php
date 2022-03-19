<?php

namespace SecureTrading\Trust\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\State;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use SecureTrading\Trust\Helper\Data;

class UpgradeData implements UpgradeDataInterface
{
	/**
	 * @var State
	 */
	private $state;

	/**
	 * @var StatusFactory
	 */
	private $statusFactory;

	private $eavSetupFactory;
	/**
	 * UpgradeData constructor.
	 *
	 * @param StatusFactory $statusFactory
	 * @param State $state
	 */
	public function __construct(
		StatusFactory $statusFactory,
		State $state,
		EavSetupFactory $eavSetupFactory
	) {
		$this->state         = $state;
		$this->statusFactory = $statusFactory;
		$this->eavSetupFactory = $eavSetupFactory;
	}

	/**
	 * @param ModuleDataSetupInterface $setup
	 * @param ModuleContextInterface $context
	 */
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();

		if (version_compare($context->getVersion(), '0.0.2', '<')) {
			$this->createSecureTradingOrderStatus();
		}

		if(version_compare($context->getVersion(), '0.0.3', '<')){
			$this->addSubscriptionAttributes($setup);
		}
		$setup->endSetup();
	}

	/**
	 * @throws \Exception
	 */
	private function createSecureTradingOrderStatus()
	{
		$this->state->emulateAreaCode(
			'global',
			function () {
				/** @var Status $status */
				$status = $this->statusFactory->create();
				$status->load(Data::ORDER_STATUS);
				$status->setData([
					'status' => Data::ORDER_STATUS,
					'label'  => Data::ORDER_STATUS_LABEL,
				]);
				$status->save();
				$status->assignState(Order::STATE_NEW, false, true);
			}
		);
	}

	private function addSubscriptionAttributes(ModuleDataSetupInterface $setup)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

		$setup->startSetup();

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'stpp_enable_subs',
			[
				'type'                    => 'int',
				'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'                 => false,
				'required'                => false,
				'user_defined'            => false,
				'default'                 => 0,
				'searchable'              => false,
				'filterable'              => false,
				'comparable'              => false,
				'visible_on_front'        => false,
				'used_in_product_listing' => true,
				'unique'                  => false,
				'apply_to'                => 'simple,virtual,downloadable,configurable'
			]
		)->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'stpp_require_subs',
			[
				'type'                    => 'int',
				'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'                 => false,
				'required'                => false,
				'user_defined'            => false,
				'default'                 => 0,
				'searchable'              => false,
				'filterable'              => false,
				'comparable'              => false,
				'visible_on_front'        => false,
				'used_in_product_listing' => true,
				'unique'                  => false,
				'apply_to'                => 'simple,virtual,downloadable,configurable',
				'note'                    => 'Require subscription option when add product to shopping cart'
			]
		)->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'stpp_options_subs',
			[
				'type'                    => 'text',
				'backend'                 => 'SecureTrading\Trust\Model\Backend\SubscriptionOptions',
				'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible'                 => false,
				'required'                => false,
				'user_defined'            => false,
				'default'                 => '',
				'searchable'              => false,
				'filterable'              => false,
				'comparable'              => false,
				'visible_on_front'        => false,
				'used_in_product_listing' => false,
				'unique'                  => false,
				'apply_to'                => 'simple,virtual,downloadable,configurable',
			]
		);

		$setup->endSetup();
	}
}
