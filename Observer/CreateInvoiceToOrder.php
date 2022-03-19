<?php

namespace Securetrading\Trust\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order;

class CreateInvoiceToOrder implements ObserverInterface
{
	/**
	 * @var \Magento\Sales\Model\Service\InvoiceService
	 */
	protected $invoiceService;

	/**
	 * @var \Magento\Framework\DB\Transaction
	 */
	protected $transaction;

	protected $serializer;

	/**
	 * @var \Magento\Sales\Model\Convert\Order
	 */
	protected $orderConverter;

	public function __construct(
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Framework\DB\Transaction $transaction,
		\Magento\Sales\Model\Convert\Order $orderConverter,
		SerializerInterface $serializer
	)
	{
		$this->invoiceService = $invoiceService;
		$this->transaction    = $transaction;
		$this->orderConverter = $orderConverter;
		$this->serializer     = $serializer;
	}

	public function execute(Observer $observer)
	{
		$order = $observer->getOrder();
		$payment = $order->getPayment();
		$stData = $payment->getAdditionalInformation('api_secure_trading_data');
		$stData = $this->serializer->unserialize($stData);
		$items = $order->getItems();
		if(!empty($stData)){
			foreach ($items as $item) {
				$options = $item->getProductOptions();
				if (isset($options["secure_trading_subscription"])) {
					if ($order->canInvoice()) {
						try {
							if($options['secure_trading_subscription']['subscriptiontype'] == 'RECURRING') {
								$payment->setAmountAuthorized($order->getTotalDue());
								$payment->setBaseAmountAuthorized($order->getBaseTotalDue());
								$payment->capture(null);

								$payment->setAdditionalInformation('is_complete', true);

								$order->setState(Order::STATE_PROCESSING);
								$order->setStatus(Order::STATE_PROCESSING);
								$order->save();
							}else{
								$this->createInstallmentInvoice($order, $stData, $options, false);
							};
						} catch (\Exception $e) {
							throw new LocalizedException(__($e->getMessage()));
						}
					}
				}
			}
		}
	}

	public function createInstallmentInvoice(Order $order, array $stData, array $options, $isFinal)
	{
		$state = Order::STATE_PROCESSING;
		$baseAmount = $stData['baseamount'];
		$mainAmount = (float)$baseAmount / 100;
		$baseAmountToCapture = (float)$mainAmount;
		$amountToCapture = (float)$mainAmount * $order->getBaseToOrderRate();
		if ($isFinal) {
			$invoice = $this->invoiceService->prepareInvoice($order);
			$invoice->setGrandTotal($invoice->getGrandTotal() + (float)$order->getShippingAmount());
			$invoice->setSubtotal($invoice->getSubTotal() + (float)$order->getShippingAmount());
			$invoice->setBaseSubTotal($invoice->getBaseSubTotal() + (float)$order->getBaseShippingAmount());
			$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + (float)$order->getBaseShippingAmount());
			$state = Order::STATE_COMPLETE;
		} else {
			$invoice = $this->orderConverter->toInvoice($order);
			$invoice->collectTotals();
			$invoice->setGrandTotal($amountToCapture);
			$invoice->setSubtotal($amountToCapture);
			$invoice->setBaseSubTotal($baseAmountToCapture);
			$invoice->setBaseGrandTotal($baseAmountToCapture);
		}
		$invoice->setRequestedCaptureCase($invoice::CAPTURE_ONLINE);
		$invoice->register();
		$transactionSave = $this->transaction->addObject(
			$invoice
		)->addObject(
			$invoice->getOrder()
		);
		$transactionSave->save();
		$order->setState($state);
		$order->setStatus($state);
		$order->save();
	}
}