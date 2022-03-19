<?php

namespace SecureTrading\Trust\Gateway\Response\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class ResponseHandle
 * @package SecureTrading\Trust\Gateway\Response\Api
 */
class ResponseHandle implements HandlerInterface
{

	/**
	 * @var PaymentTokenFactoryInterface
	 */
	protected $paymentTokenFactory;
	/**
	 * @var Json
	 */
	protected $json;
	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;
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
	 * ResponseHandle constructor.
	 * @param PaymentTokenFactoryInterface $paymentTokenFactory
	 * @param Json $json
	 * @param SubscriptionHelper $subscriptionHelper
	 */
	public function __construct(
		PaymentTokenFactoryInterface $paymentTokenFactory,
		Json $json,
		SubscriptionHelper $subscriptionHelper,
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Framework\DB\Transaction $transaction,
		\Magento\Sales\Model\Convert\Order $orderConverter,
		Logger $logger
	)
	{
		$this->paymentTokenFactory = $paymentTokenFactory;
		$this->json                = $json;
		$this->subscriptionHelper  = $subscriptionHelper;
		$this->logger         = $logger;
		$this->invoiceService = $invoiceService;
		$this->transaction    = $transaction;
		$this->orderConverter = $orderConverter;
	}

	/**
	 * @param array $handlingSubject
	 * @param array $response
	 * @throws LocalizedException
	 */
	public function handle(array $handlingSubject, array $response)
	{
		$paymentDO = SubjectReader::readPayment($handlingSubject);

		/** @var Payment $payment */
		$payment = $paymentDO->getPayment();

		$order   = $payment->getOrder();

		$items   = $order->getItems();

		$item = reset($items);

		$additionalData = $payment->getAdditionalInformation();

		if (!empty($response)) {
			if (isset($response['transactionreference'])) {
				//set action authorize_capture for subscription
				$payment->setAdditionalInformation('payment_action', $response['settlestatus'] == 2 ? 'authorize' : 'authorize_capture');
				$payment->setAdditionalInformation('orderreference', $order->getIncrementId());
				$payment->setAdditionalInformation('paymenttypedescription', $response['paymenttypedescription']);
				$payment->setAdditionalInformation('transactionreference', $response['transactionreference']);
				$payment->setAdditionalInformation('maskedpan', $response['maskedpan']);
				if (!empty($payment->getAdditionalInformation('transactionreference'))) {
					$payment->setTransactionId($payment->getAdditionalInformation('transactionreference'));
					$payment->setIsTransactionClosed(0);
				} else {
					throw new LocalizedException(__('Transaction Reference was not found.'));
				}
			} else {
//				$payment->setAdditionalInformation('secure_trading_data', $response);
				$payment->setAdditionalInformation('payment_action', $response['settlestatus'] == 2 ? 'authorize' : 'authorize_capture');
			}
			// Handel issubscription
			if (isset($item->getProductOptions()['secure_trading_subscription'])) {
				$payment->setAdditionalInformation('issubscription', 'issubscription');
				if (isset($response['settlestatus'])) {
					if (in_array($response['settlestatus'], [0, 1, 10])) {
						$items = $order->getItems();
						foreach ($items as $item) {
							$options = $item->getProductOptions();
							if (isset($options["secure_trading_subscription"])) {
								$this->subscriptionHelper->processSubscription($payment, $response, $options);
							}
						}
					} else {
						throw new LocalizedException(__('Settle status is incorrect.'));
					}
				} else {
					throw new LocalizedException(__('Settle status was not found.'));
				}
			}
		}

		$order->setState(Order::STATE_NEW);
		$order->setStatus('pending_secure_trading_api_payment');

		//Save card credit
		if (isset($additionalData['save_card_info_api']) && $additionalData['save_card_info_api']) {
			$this->subscriptionHelper->saveCreditCard($response, $payment, $order);
		}
	}
}
