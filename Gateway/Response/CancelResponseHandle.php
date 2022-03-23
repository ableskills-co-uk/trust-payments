<?php

namespace SecureTrading\Trust\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Class CancelResponseHandle
 * @package SecureTrading\Trust\Gateway\Response
 */
class CancelResponseHandle implements HandlerInterface
{
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

        $payment->setIsTransactionClosed(true);
        $payment->setAdditionalInformation('settlestatus',3);
    }
}