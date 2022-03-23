<?php

namespace Securetrading\Trust\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CreateOrderObserver
 * @package Securetrading\Trust\Observer
 */
class CreateOrderObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * CreateOrderObserver constructor.
     * @param \Magento\Framework\Registry $registry
     */
    function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     */
    function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $this->registry->register('order_id',$order->getId());
    }
}