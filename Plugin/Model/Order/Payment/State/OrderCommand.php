<?php

namespace SecureTrading\Trust\Plugin\Model\Order\Payment\State;

use SecureTrading\Trust\Model\Ui\ConfigProvider;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class OrderCommand
 * @package SecureTrading\Trust\Plugin\Model\Order\Payment\State
 */
class OrderCommand
{
    /**
     * @param $subject
     * @param \Closure $proceed
     * @param OrderPaymentInterface $payment
     * @param $amount
     * @param OrderInterface $order
     * @return \Magento\Framework\Phrase|mixed
     */
    function aroundExecute(
        $subject,
        \Closure $proceed,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        if ($payment->getMethod() == ConfigProvider::CODE || $payment->getMethod() == ConfigProvider::API_CODE) {
            $message = 'Ordered amount of %1';

            return __($message, $order->getBaseCurrency()->formatTxt($amount));
        } else return $proceed($payment, $amount, $order);
    }
}
