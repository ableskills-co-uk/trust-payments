<?php

namespace SecureTrading\Trust\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

/**
 * Class VoidMultiShippingOrder
 * @package SecureTrading\Trust\Observer\Payment
 */
class VoidMultiShippingOrder extends AbstractOperationObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    function execute(Observer $observer)
	{
		$flag = $this->coreRegistry->registry('void_multishipping');
		if ($flag != true) {
			$payment = $observer->getEvent()->getPayment();
			$order = $payment->getOrder();
			if ($setId = $payment->getAdditionalInformation('multishipping_set_id')) {
				$multiShipping = $this->multiShippingFactory->create()->load($setId);
				if ($multiShipping->getSetId()) {
					$this->logger->debug('--- VOID LIST ORDER IDS: ' . $multiShipping->getListOrders());
					$listId = $this->serializer->unserialize($multiShipping->getListOrders());
					unset($listId[$order->getId()]);
					$collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => array_keys($listId)]);
					$this->coreRegistry->register('void_multishipping', true);
					foreach ($collection as $item) {
						if ($item->getId() && ($item->getState() != Order::STATE_COMPLETE || $item->getState() != Order::STATE_CLOSED)){
							$relatedPayment = $item->getPayment();
							$relatedPayment->void(new \Magento\Framework\DataObject());
                            $item->setState(Order::STATE_CLOSED);
                            $item->setStatus(Order::STATE_CLOSED);
							$item->save();
							$this->logger->debug('--- Order increment id: ' . $item->getId() . ' has been voided');
						} else {
							throw new LocalizedException(__('Can\'t void the related payment.'));
						}
					}
					$this->coreRegistry->unregister('void_multishipping');
				} else {
					throw new LocalizedException(__('Can\'t void the related payment.'));
				}
			}
		}
	}
}