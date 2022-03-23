<?php

namespace SecureTrading\Trust\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Model\MultiShippingFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\RefundInvoiceInterface;

/**
 * Class RefundMultiShippingOrder
 * @package SecureTrading\Trust\Observer\Payment
 */
class RefundMultiShippingOrder extends AbstractOperationObserver implements ObserverInterface
{
    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var RefundInvoiceInterface
     */
    protected $refundInvoice;

    /**
     * RefundMultiShippingOrder constructor.
     * @param RefundInvoiceInterface $refundInvoice
     * @param Invoice $invoice
     * @param MultiShippingFactory $multiShippingFactory
     * @param CollectionFactory $collectionFactory
     * @param SerializerInterface $serializer
     * @param Registry $coreRegistry
     * @param Logger $logger
     */
    function __construct(RefundInvoiceInterface $refundInvoice,
								Invoice $invoice,
								MultiShippingFactory $multiShippingFactory,
								CollectionFactory $collectionFactory,
								SerializerInterface $serializer,
								Registry $coreRegistry,
								Logger $logger)
    {
        $this->refundInvoice = $refundInvoice;
        $this->invoice = $invoice;
        parent::__construct($multiShippingFactory, $collectionFactory, $serializer, $coreRegistry, $logger);
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    function execute(Observer $observer)
    {
        $flag = $this->coreRegistry->registry('refund_multishipping');
        $isSettled = $this->coreRegistry->registry('is_settled');
        if ($flag != true && $isSettled != true)
        {
            $payment = $observer->getEvent()->getPayment();
            $order = $payment->getOrder();
            if ($setId = $payment->getAdditionalInformation('multishipping_set_id'))
            {
                $multiShipping = $this->multiShippingFactory->create()->load($setId);

                if ($multiShipping->getSetId()) {
                    $this->logger->debug('--- LIST ORDER IDS: ' . $multiShipping->getListOrders());
                    $listId = $this->serializer->unserialize($multiShipping->getListOrders());
                    unset($listId[$order->getId()]);
                    $collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => array_keys($listId)]);
                    $this->coreRegistry->register('refund_multishipping', true);
                    foreach ($collection as $item) {
                        if ($item->getId() && ($item->getState() != Order::STATE_COMPLETE || $item->getState() != Order::STATE_CLOSED)){
                            $this->refundOrderRelated($item);
                            $this->logger->debug('--- Order increment id: ' . $item->getId() . ' has been refunded');
                        } else {
                            throw new LocalizedException(__('Can\'t refund the related payment.'));
                        }
                    }
                    $this->coreRegistry->unregister('refund_securetrading');
                    $this->coreRegistry->unregister('refund_multishipping');
                } else {
                    throw new LocalizedException(__('Can\'t refund the related payment.'));
                }
            }
        }
    }

    /**
     * Refund Order Related
     * @param $order
     */
    function refundOrderRelated($order)
    {
        $invoiceIncrementId = $order->getInvoiceCollection()->getData()[0]['increment_id'];
        $invoiceObject = $this->invoice->loadByIncrementId($invoiceIncrementId);
        $invoiceObject->setBaseTotalRefunded(round($order->getBaseGrandTotal(),1));
        $invoiceObject->setIsUsedForRefund(true);
        $this->refundInvoice->execute($invoiceObject->getId(),[],true);
    }
}