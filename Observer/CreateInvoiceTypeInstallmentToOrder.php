<?php

namespace Securetrading\Trust\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CreateInvoiceTypeInstallmentToOrder implements ObserverInterface
{
	protected $registry;
	function __construct(\Magento\Framework\Registry $registry)
	{
		$this->registry = $registry;
	}
	function execute(Observer $observer)
	{
		$payment = $observer->getPayment();
		$order = $payment->getOrder();
		$additionalInformation = $payment->getAdditionalInformation();
		$items = $order->getItems();
			foreach ($items as $item) {
				$options = $item->getProductOptions();
				if (isset($options["secure_trading_subscription"]) && $options["secure_trading_subscription"]['subscriptiontype'] === 'INSTALLMENT' && isset($additionalInformation['payment_action']) && $additionalInformation['payment_action'] === 'authorize_capture') {
					$this->registry->register('is_subscription',true);
				}
			}
	}
}