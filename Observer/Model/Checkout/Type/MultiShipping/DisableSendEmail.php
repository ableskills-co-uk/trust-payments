<?php

namespace SecureTrading\Trust\Observer\Model\Checkout\Type\MultiShipping;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class DisableSendEmail
 *
 * @package SecureTrading\Trust\Observer\Model\Checkout\Type\MultiShipping
 */
class DisableSendEmail implements ObserverInterface
{

	/**
	 * Prevent send email before paying
	 * @param Observer $observer
	 * @throws \Exception
	 */
	function execute(Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		$order->setCanSendNewEmailFlag(false);
		$order->save();
	}
}