<?php

namespace SecureTrading\Trust\Model\Config\Backend;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SubscriptionUrl
 *
 * @package SecureTrading\Trust\Model\Config\Backend
 */
class SubscriptionUrl extends \Magento\Framework\App\Config\Value
{
	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * SubscriptionUrl constructor.
	 *
	 * @param StoreManagerInterface $storeManager
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
	 * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
	 * @param array $data
	 */
	function __construct(StoreManagerInterface $storeManager,
								   \Magento\Framework\Model\Context $context,
								   \Magento\Framework\Registry $registry,
								   \Magento\Framework\App\Config\ScopeConfigInterface $config,
								   \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
								   \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
								   \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
								   array $data = [])
	{
		$this->storeManager = $storeManager;
		parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
	}

	/**
	 * @return \Magento\Framework\App\Config\Value|void
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	function afterLoad()
	{
		$this->setValue($this->storeManager->getStore()->getBaseUrl() . 'securetrading/subscription/notificationresponse');
		parent::beforeSave();
	}
}