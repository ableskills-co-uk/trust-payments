<?php

namespace SecureTrading\Trust\Gateway\Response\Api;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class RefundResponseHandle implements HandlerInterface
{
	public function handle(array $handlingSubject, array $response)
	{
		$paymentDO = SubjectReader::readPayment($handlingSubject);

		/** @var Payment $payment */
		$payment = $paymentDO->getPayment();

		$payment->setIsTransactionClosed(true);
	}
}