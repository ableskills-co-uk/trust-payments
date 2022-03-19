<?php

namespace SecureTrading\Trust\Observer\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class Save
 *
 * @package SecureTrading\Trust\Observer\Product
 */
class Save implements ObserverInterface
{
	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var ProductRepositoryInterface
	 */
	protected $productRepository;

	/**
	 * @var \Magento\Framework\Message\ManagerInterface
	 */
	protected $messageManager;

	/**
	 * @var \Magento\Framework\Serialize\Serializer\Json
	 */
	protected $json;

	/**
	 * Save constructor.
	 *
	 * @param RequestInterface $requestInterface
	 * @param StoreManagerInterface $storeManagerInterface
	 * @param ProductRepositoryInterface $productRepository
	 * @param \Magento\Framework\Message\ManagerInterface $messageManager
	 * @param \Magento\Framework\Serialize\Serializer\Json $json
	 */
	public function __construct(RequestInterface $requestInterface,
								   StoreManagerInterface $storeManagerInterface,
								   ProductRepositoryInterface $productRepository,
								   \Magento\Framework\Message\ManagerInterface $messageManager,
								   \Magento\Framework\Serialize\Serializer\Json $json)
	{
		$this->request           = $requestInterface;
		$this->storeManager      = $storeManagerInterface;
		$this->productRepository = $productRepository;
		$this->messageManager    = $messageManager;
		$this->json              = $json;


	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$params = $this->request->getParams();
		$product = $observer->getProduct();
		$isEnableSubscription = isset($params['product'][Data::ATT_ENABLE_SUBS]) ? $params['product'][Data::ATT_ENABLE_SUBS] : 0;
		if($isEnableSubscription){
			if(isset($params['product'][Data::ATT_OPTIONS_SUBS])){
				$product->setData(Data::ATT_OPTIONS_SUBS,$this->json->serialize($params['product'][Data::ATT_OPTIONS_SUBS]));
			}
			else{
				$product->setData(Data::ATT_ENABLE_SUBS,0);
				$product->setData(Data::ATT_REQUIRE_SUBS,0);
			}
		}
		return;
	}
}