<?php

namespace SecureTrading\Trust\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class IncludeJsFileDependRegion
 * @package SecureTrading\Trust\Observer
 */
class IncludeJsFileDependRegion extends AbstractDataAssignObserver
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

	/**
	 * @var Session
	 */
    protected $customerSession;

	/**
	 * IncludeJsFileDependRegion constructor.
	 *
	 * @param StoreManagerInterface $storeManager
	 * @param Session $customerSession
	 */
    function __construct(StoreManagerInterface $storeManager, Session $customerSession)
    {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    function execute(Observer $observer)
    {
        $checkLayout = $observer->getFullActionName();
        if ($checkLayout == "checkout_index_index"
	        && $this->storeManager->getStore()->getConfig(Data::IS_TOKENIZATION == 1)
	        && ($this->storeManager->getStore()->getConfig('payment/secure_trading/active') == 1
		        || $this->storeManager->getStore()->getConfig('payment/api_secure_trading/active') == 1)
	        && $this->customerSession->isLoggedIn())
        {
            $region = $this->storeManager->getStore()->getConfig('payment/secure_trading/endpoint');
            $region == "https://payments.securetrading.net/"
                ? $jsIncludetoLayout = "checkout_include_js_uk"
                : $jsIncludetoLayout = "checkout_include_js_us";
            $layout = $observer->getLayout();
            $layout->getUpdate()->addHandle($jsIncludetoLayout);
        }
        return $this;
    }
}