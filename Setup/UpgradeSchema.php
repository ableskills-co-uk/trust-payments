<?php

namespace SecureTrading\Trust\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 *
 * @package SecureTrading\Trust\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
	/**
	 *
	 */
	CONST SUBSCRIPTION_TABLE = "secure_trading_subscription";
	CONST MULTISHIPPING_TABLE = "secure_trading_multishipping";

	/**
	 * @param SchemaSetupInterface $setup
	 * @param ModuleContextInterface $context
	 * @throws \Zend_Db_Exception
	 */
	public function upgrade(SchemaSetupInterface $setup,
							ModuleContextInterface $context)
	{
		if (version_compare($context->getVersion(), '0.0.4') < 0) {
			$this->addSecureTradingSubscriptionTable($setup);
		}
		if (version_compare($context->getVersion(), '0.0.5') < 0) {
			$this->updateOrderIdColumnForSubscriptionTable($setup);
		}
		if (version_compare($context->getVersion(), '0.0.6') < 0) {
			$this->addSecureTradingMultiShippingTable($setup);
		}
	}

	/**
	 * @param $setup
	 */
	private function addSecureTradingSubscriptionTable($setup)
	{
		$installer = $setup;
		$installer->startSetup();

		/*CREATE secure_trading_subscription*/
		$table = $installer->getConnection()->newTable(
			$installer->getTable(self::SUBSCRIPTION_TABLE)
		)->addColumn(
			'id',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null, [
			'identity' => true,
			'unsigned' => true,
			'nullable' => false,
			'primary'  => true],
			'ID'
		)->addColumn(
			'order_id',
			\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			255,
			['nullable' => false],
			'Order ID'
		)->addColumn(
			'transaction_id',
			\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			255,
			['nullable' => false],
			'Transaction ID'
		)->addColumn(
			'unit',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null,
			['nullable' => false],
			'Unit'
		)->addColumn(
			'frequency',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null,
			['nullable' => false],
			'Frequency'
		)->addColumn(
			'final_number',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null,
			['nullable' => false],
			'Final Number'
		)->addColumn(
			'type',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null,
			['nullable' => false],
			'Type'
		)->addColumn(
			'created_at',
			\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
			null,
			[
				'nullable' => false,
				'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
			],
			'Created At'
		)->addColumn(
			'status',
			\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			255,
			['nullable' => false],
			'Status'
		)->addColumn(
			'skip_the_first_payment',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null,
			['nullable' => false],
			'Status'
		)->setComment(
			'Secure Trading Subscription'
		);

		$installer->getConnection()->createTable($table);

		$installer->endSetup();
	}

	private function updateOrderIdColumnForSubscriptionTable($setup)
	{
		$installer = $setup;
		$installer->startSetup();

		$connection = $installer->getConnection();

		$connection->modifyColumn(
			$setup->getTable(self::SUBSCRIPTION_TABLE),
			'order_id',
			[
				'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length' => 32,
			]
		);
		$connection->addColumn(
			$installer->getTable(self::SUBSCRIPTION_TABLE),
			'number',
			[
				'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				'comment' => 'number'
			]
		);
		$connection->addColumn(
			$installer->getTable(self::SUBSCRIPTION_TABLE),
			'parent_order_id',
			[
				'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length' => 32,
				'nullable' => false,
				'comment' => 'Parent Order ID'
			]
		);
		$connection->addColumn(
			$installer->getTable(self::SUBSCRIPTION_TABLE),
			'parent_transaction_id',
			[
				'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length' => 255,
				'nullable' => false,
				'comment' => 'Parent Transaction ID'
			]
		);

		$installer->endSetup();
	}

	/**
	 * @param $setup
	 */
	private function addSecureTradingMultiShippingTable($setup)
	{
		$installer = $setup;
		$installer->startSetup();

		/*CREATE secure_trading_subscription*/
		$table = $installer->getConnection()->newTable(
			$installer->getTable(self::MULTISHIPPING_TABLE)
		)->addColumn(
			'set_id',
			\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			null, [
			'identity' => true,
			'unsigned' => true,
			'nullable' => false,
			'primary'  => true],
			'Set ID'
		)->addColumn(
			'list_orders',
			\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			null,
			['nullable' => false],
			'List Orders'
		)->addColumn(
			'created_at',
			\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
			null,
			[
				'nullable' => false,
				'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
			],
			'Created At'
		)->setComment(
			'Secure Trading MultiShipping'
		);

		$installer->getConnection()->createTable($table);

		$installer->endSetup();
	}
}