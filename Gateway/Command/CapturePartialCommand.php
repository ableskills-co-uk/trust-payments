<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Payment\Processor;
use SecureTrading\Trust\Helper\Logger\Logger;
use Magento\Framework\Registry;

/**
 * Class CapturePartialCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class CapturePartialCommand implements CommandInterface
{
	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var \Magento\Sales\Model\Service\InvoiceService
	 */
	protected $invoiceService;

	/**
	 * @var \Magento\Framework\DB\Transaction
	 */
	protected $transaction;

	/**
	 * @var \Magento\Sales\Model\Convert\Order
	 */
	protected $orderConverter;

	/**
	 * @var CommandPoolInterface
	 */
	protected $commandPool;

	protected $registry;

	public function __construct(
		CommandPoolInterface $commandPool,
		Logger $logger,
		\Magento\Framework\App\Action\Context $context,
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Framework\DB\Transaction $transaction,
		\Magento\Sales\Model\Convert\Order $orderConverter,
		Registry $registry
	)
	{
		$this->logger = $logger;
		$this->invoiceService = $invoiceService;
		$this->transaction = $transaction;
		$this->orderConverter = $orderConverter;
		$this->commandPool = $commandPool;
		$this->registry = $registry;
	}

	/**
	 * @param array $commandSubject
	 * @return void|null
	 * @throws LocalizedException
	 */
	public function execute(array $commandSubject)
	{
		/** @var Order $order */
		if (!empty($commandSubject['order'])) {
			$order = $commandSubject['order'];
		} else {
			throw new LocalizedException(__('The order was not found.'));
		}

		if (!$order || !$order->getId()) {
			throw new LocalizedException(__('The order no longer exists.'));
		}
		$payment = $order->getPayment();
		$this->logger->debug('--- INCREMENT ORDER ID : ' . $order->getIncrementId() . '---');
		if (!empty($commandSubject['info']['transactionreference'])) {
			$this->setTransactionData($payment, $commandSubject);
		} else {
			throw new LocalizedException(__('Transaction reference was not found.'));
		}

		if (isset($commandSubject['info']['settlestatus'])) {
			if (in_array($commandSubject['info']['settlestatus'], [0, 1, 10])) {
				$stData = $payment->getAdditionalInformation('secure_trading_data');
				$stDataApi = $payment->getAdditionalInformation('api_secure_trading_data');
				$items = $payment->getOrder()->getAllItems();
				$item = reset($items);
				$optionSubs = $item->getData('product_options')['secure_trading_subscription'];
				if ($order->canInvoice()) {
					try {
						if (empty($commandSubject['info']['subscriptionnumber']) && empty($commandSubject['info']['parenttransactionreference'])) {
							if (empty($commandSubject['info']['skipthefirstpayment'])) {
								$this->createInstallmentInvoice($order, $stData, false);
							} else {
								$this->authorizeCommand($order, $commandSubject['info']);
							}
						} elseif (isset($commandSubject['info']['subscriptionnumber']) && (isset($stData['subscriptionfinalnumber']) || isset($optionSubs['subscriptionfinalnumber']))) {
							$commandSubject['info']['subscriptionnumber'] == $stData['subscriptionfinalnumber'] ? $isFinal = true : $isFinal = false;
							$this->createInstallmentInvoice($order, $stData, $isFinal);
						}
					} catch (\Exception $e) {
						$this->logger->debug($e->getMessage());
					}
				}
			} else {
				throw new LocalizedException(__('Settle status is incorrect.'));
			}
		} else {
			throw new LocalizedException(__('Settle status was not found.'));
		}
	}

	public function createInstallmentInvoice(Order $order, $stData, $isFinal)
	{
		$state = Order::STATE_PROCESSING;
		$baseAmountToCapture = (float)$stData['mainamount'];
		$amountToCapture = (float)$stData['mainamount'] * $order->getBaseToOrderRate();
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

	/**
	 * @param Order $order
	 * @param array $responseParams
	 * @throws \Magento\Framework\Exception\NotFoundException
	 * @throws \Magento\Payment\Gateway\Command\CommandException
	 */
	public function authorizeCommand(Order $order, array $responseParams)
	{
		$this->commandPool->get('authorize')->execute(['order' => $order, 'info' => $responseParams]);
	}

	/**
	 * @param $payment
	 * @param $commandSubject
	 */
	public function setTransactionData($payment, $commandSubject)
	{
		$payment->setTransactionId($commandSubject['info']['transactionreference']);
		if (isset($commandSubject['info']['parenttransactionreference'])) {
			$payment->setParentTransactionId($commandSubject['info']['parenttransactionreference']);
			$payment->setIsTransactionClosed(1);
		} else {
			$payment->setParentTransactionId($commandSubject['info']['transactionreference']);
			$payment->setIsTransactionClosed(0);
		}
		$payment->setTransactionAdditionalInfo('transaction_data', $commandSubject['info']);
	}
}