<?php

namespace Securetrading\Trust\Plugin\Order\Email\Container;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Session\Quote as AdminCheckoutSession;

class OrderIdentityPlugin extends \Magento\Sales\Model\Order\Email\Container\OrderIdentity
{
	private $adminCheckoutSession;
	function __construct(ScopeConfigInterface $scopeConfig, StoreManagerInterface $storeManager, AdminCheckoutSession $adminCheckoutSession)
	{
		parent::__construct($scopeConfig, $storeManager);
		$this->adminCheckoutSession = $adminCheckoutSession;
	}

	function afterIsEnabled(\Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject, $result)
	{
		$quote = $this->adminCheckoutSession->getQuote();
		$method = $quote->getPayment()->getMethod();
		if( $method == 'api_secure_trading' || $method == 'secure_trading')
			return false;
        return $result;
   }
}