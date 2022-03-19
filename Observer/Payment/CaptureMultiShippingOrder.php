<?php

namespace SecureTrading\Trust\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
/**
 * Class CaptureMultiShippingOrder
 *
 * @package SecureTrading\Trust\Observer\Payment
 */
class CaptureMultiShippingOrder extends AbstractOperationObserver implements ObserverInterface
{
	/**
	 * @param Observer $observer
	 * @throws LocalizedException
	 */
	public function execute(Observer $observer)
	{
		$flag = $this->coreRegistry->registry('capture_multishipping');
		if ($flag != true) {
			$payment = $observer->getEvent()->getPayment();
			$order   = $observer->getEvent()->getInvoice()->getOrder();
			if ($setId = $payment->getAdditionalInformation('multishipping_set_id')) {
				$multiShipping = $this->multiShippingFactory->create()->load($setId);
				if ($multiShipping->getSetId()) {
					$this->logger->debug('--- CAPTURE LIST ORDER IDS: ' . $multiShipping->getListOrders());
					$listId = $this->serializer->unserialize($multiShipping->getListOrders());
					unset($listId[$order->getId()]);
					$collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => array_keys($listId)]);
					$this->coreRegistry->register('capture_multishipping', true);
					foreach ($collection as $item) {
						if ($item->getId() && ($item->getState() != Order::STATE_COMPLETE || $item->getState() != Order::STATE_CLOSED))
						{
							$relatedPayment = $item->getPayment();
							$relatedPayment->setAmountAuthorized($item->getTotalDue());
							$relatedPayment->setBaseAmountAuthorized($item->getBaseTotalDue());
							$relatedPayment->capture(null);

							$relatedPayment->setAdditionalInformation('is_complete', true);

							$item->setState(Order::STATE_PROCESSING);
							$item->setStatus(Order::STATE_PROCESSING);
							$item->save();
							$this->logger->debug('--- Order increment id: ' . $item->getId() . 'has been captured');
						} else {
							throw new LocalizedException(__('Can\'t capture the related order.'));
						}
					}
				} else {
					throw new LocalizedException(__('Can\'t capture the related order.'));
				}
			}
		}
	}
}