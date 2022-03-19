<?php

namespace SecureTrading\Trust\Gateway\Response\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use SecureTrading\Trust\Helper\SubscriptionHelper;
use SecureTrading\Trust\Model\MultiShippingFactory;

class MultiShippingResponseHandle implements HandlerInterface
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

	protected $multiShippingFactory;

	protected $orderFactory;

	public function __construct(
		PaymentTokenFactoryInterface $paymentTokenFactory,
		Json $json,
		SubscriptionHelper $subscriptionHelper,
		MultiShippingFactory $multiShippingFactory,
		OrderFactory $orderFactory
	)
	{
		$this->paymentTokenFactory  = $paymentTokenFactory;
		$this->json                 = $json;
		$this->subscriptionHelper   = $subscriptionHelper;
		$this->multiShippingFactory = $multiShippingFactory;
		$this->orderFactory         = $orderFactory;
	}

	/**
	 * @param array $handlingSubject
	 * @param array $response
	 * @throws LocalizedException
	 */
	public function handle(array $handlingSubject, array $response)
	{
		$paymentDO = $handlingSubject;
		$multiShippingId = $paymentDO['data']['multishippingsetid'];
		$listOrders = $this->multiShippingFactory->create()->load($multiShippingId)->getListOrders();
		$listOrders = json_decode($listOrders);
		foreach ($listOrders as $key => $value){

			$order = $this->orderFactory->create()->loadByIncrementId($value);
			/** @var Payment $payment */
			$payment = $order->getPayment();
			$invoice = $order->getInvoiceCollection()->getFirstItem();
			if (!empty($response)) {
				$payment->setAdditionalInformation('api_secure_trading_data', $response);
				if(!empty($response['issubscription'])){
					$payment->setAdditionalInformation('payment_action','authorize_capture');
				} else {
					//set action authorize_capture for subscription
					$payment->setAdditionalInformation('payment_action', $response['settlestatus'] == 2 ? 'authorize' : 'authorize_capture');
					$payment->setAdditionalInformation('orderreference', $response['orderreference']);
					$payment->setAdditionalInformation('paymenttypedescription', $response['paymenttypedescription']);
					$payment->setAdditionalInformation('transactionreference', $response['transactionreference']);
					$payment->setAdditionalInformation('maskedpan', $response['maskedpan']);
					$payment->setAdditionalInformation('authcode', $response['authcode']);
					if (!empty($payment->getAdditionalInformation('transactionreference'))) {
						$payment->setTransactionId($payment->getAdditionalInformation('transactionreference'));
						$payment->setIsTransactionClosed(0);
						if(!empty($invoice->getId())){
							$invoice->setTransactionId($payment->getAdditionalInformation('transactionreference'));
							$invoice->save();
						}
					} else {
						throw new LocalizedException(__('Transaction Reference was not found.'));
					}
				}
			}
			$payment->save();
			$order->setState(Order::STATE_NEW);
			$order->setStatus('pending_secure_trading_api_payment');
		}
	}
}
