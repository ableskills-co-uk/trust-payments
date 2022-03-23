<?php

namespace SecureTrading\Trust\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class SubscriptionStopResponseHandle
 *
 * @package SecureTrading\Trust\Gateway\Response
 */
class SubscriptionStopResponseHandle implements HandlerInterface
{
    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    function handle(array $handlingSubject, array $response)
    {
        $subscription = $handlingSubject['subscription'];
		$subscription->setStatus(2);
		$subscription->save();
    }
}