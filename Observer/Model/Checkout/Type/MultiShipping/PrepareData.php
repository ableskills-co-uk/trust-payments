<?php

namespace SecureTrading\Trust\Observer\Model\Checkout\Type\MultiShipping;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use SecureTrading\Trust\Helper\MultiShippingHelper;

/**
 * Class PrepareData
 *
 * @package SecureTrading\Trust\Observer\Model\Checkout\Type\MultiShipping
 */
class PrepareData implements ObserverInterface
{
	/**
	 * @var MultiShippingHelper
	 */
	protected $helper;

	/**
	 * PrepareData constructor.
	 *
	 * @param MultiShippingHelper $helper
	 */
	function __construct(MultiShippingHelper $helper)
	{
		$this->helper = $helper;
	}

	/**
	 * @param Observer $observer
	 * @throws \Exception
	 */
	function execute(Observer $observer)
	{
		$orders = $observer->getEvent()->getOrders();
		$allOrders = $orders;
		$quote  = $observer->getEvent()->getQuote();
		if ($quote->getPayment()->getMethod() == \SecureTrading\Trust\Model\Ui\ConfigProvider::CODE || $quote->getPayment()->getMethod() == \SecureTrading\Trust\Model\Ui\ConfigProvider::API_CODE) {
			$currency   = $quote->getBaseCurrencyCode();
			$mainAmount = $this->helper->formatMainAmount($currency, $quote->getBaseGrandTotal());
			$multiShipping = $this->helper->saveMultiShippingData();
			if ($setId = $multiShipping->getSetId()) {
				$order = reset($allOrders);
				/** @var $order \Magento\Sales\Model\Order  * */
				if ($order->getId()) {
					$data                      = $this->helper->getPaymentAdditionalInformation($order);
					if(isset($data['mainamount'])){
						$data['mainamount']        = $mainAmount;
					}elseif (isset($data['requestData']['baseamount'])){
						$data['requestData']['baseamount'] = !strpos($mainAmount,'.') ? (string)$mainAmount : (string)str_replace('.', '', $mainAmount);
					}
					$data['ismultishipping']   = 1;
					$orderIds[$order->getId()] = $order->getIncrementId();
				}
				$data['multishippingsetid'] = $setId;
				$data                       = $this->helper->reHashData($data);
				$this->helper->saveParentOrderData($orders, $data, $setId);
			} else {
				throw new LocalizedException(__('The order ids no longer exists.'));
			}
		}
	}
}