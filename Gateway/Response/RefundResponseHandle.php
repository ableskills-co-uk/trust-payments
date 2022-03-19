<?php

namespace SecureTrading\Trust\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Class RefundResponseHandle
 * @package SecureTrading\Trust\Gateway\Response
 */
class RefundResponseHandle implements HandlerInterface
{
    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $data = $response['data'];

        $payment->setAdditionalInformation('transactionreference', $data['transactionreference']);
        $payment->setTransactionId($data['transactionreference']);

        $payment->setIsTransactionClosed(true);
    }
}