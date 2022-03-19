<?php

namespace SecureTrading\Trust\Observer\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use SecureTrading\Trust\Observer\Payment\AbstractOperationObserver;

/**
 * Class CancelMultiShippingOrder
 *
 * @package SecureTrading\Trust\Observer\Order
 */
class CancelMultiShippingOrder extends AbstractOperationObserver implements ObserverInterface
{
	/**
	 * @param Observer $observer
	 * @throws LocalizedException
	 */
	public function execute(Observer $observer)
	{
		$flag = $this->coreRegistry->registry('cancel_multishipping');
		if ($flag != true) {
			$order   = $observer->getEvent()->getOrder();
			$payment = $order->getPayment();
			if ($setId = $payment->getAdditionalInformation('multishipping_set_id')) {
				$multiShipping = $this->multiShippingFactory->create()->load($setId);
				if ($multiShipping->getSetId()) {
					$this->logger->debug('--- CANCEL LIST ORDER IDS: '. $multiShipping->getListOrders());
					$listId = $this->serializer->unserialize($multiShipping->getListOrders());
					unset($listId[$order->getId()]);
					$collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => array_keys($listId)]);
					$this->coreRegistry->register('cancel_multishipping', true);
					foreach ($collection as $item) {
						if ($item->getId() && $item->isCanceled() == false) {
							$item->cancel();
							$item->save();
							$this->logger->debug('--- Order increment id: ' . $item->getId(). 'has been cancelled');
						} else {
							throw new LocalizedException(__('Can\'t cancel the related order.'));
						}
					}
				} else {
					throw new LocalizedException(__('Can\'t cancel the related order.'));
				}
			}
		}
	}
}