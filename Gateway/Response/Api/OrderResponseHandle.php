<?php

namespace SecureTrading\Trust\Gateway\Response\Api;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Pricing\Helper\Data;

class OrderResponseHandle implements HandlerInterface
{
	protected $formatPrice;

	function __construct(Data $formatPrice)
	{
		$this->formatPrice = $formatPrice;
	}

	/**
	 * @param array $handlingSubject
	 * @param array $response
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	function handle(array $handlingSubject, array $response)
	{
		$paymentDO = SubjectReader::readPayment($handlingSubject);

		/** @var Payment $payment */
		$payment = $paymentDO->getPayment();
		$order   = $payment->getOrder();

		if (!empty($response)) {
			$payment->setAdditionalInformation('secure_trading_endpoint', $response['url']);
			unset($response['url']);
			$payment->setAdditionalInformation('secure_trading_data', $response);
			if (!empty($response['issubscription'])) {
				$payment->setAdditionalInformation('payment_action', 'authorize_capture');
				$payment->setAdditionalInformation('subscriptionamount', $this->formatPrice->currency($response['mainamount'], true, false));
			}
		}

		$order->setState(Order::STATE_NEW);
		$order->setStatus('pending_secure_trading_payment');
		$order->setCanSendNewEmailFlag(false);
		$order->save();
	}
}
