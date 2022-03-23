<?php

namespace SecureTrading\Trust\Observer\Layout;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class Load
 *
 * @package SecureTrading\Trust\Observer\Layout
 */
class Load implements ObserverInterface
{
	/**
	 *
	 */
	const OPTION_LAYOUT = [
		'catalog_product_view_type_downloadable',
		'catalog_product_view_type_configurable'
	];

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 */
	function execute(\Magento\Framework\Event\Observer $observer)
	{
		$fullActionName = $observer->getEvent()->getFullActionName();

		/** @var \Magento\Framework\View\Layout $layout */
		$layout  = $observer->getEvent()->getLayout();
		$handler = '';
		if (($fullActionName == 'catalog_product_view')
			|| ($fullActionName == 'checkout_cart_configure')) {
			$handler    = 'catalog_product_view_secure_trading_form';
			$handleData = $layout->getUpdate()->getHandles();
			foreach ($handleData as $value) {
				if (in_array($value, self::OPTION_LAYOUT)) {
					$handler = 'catalog_product_view_secure_trading_options';
				}
			}

		}

		if ($handler) {
			$layout->getUpdate()->addHandle($handler);
		}
	}
}