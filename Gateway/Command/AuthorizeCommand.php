<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class AuthorizeCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class AuthorizeCommand implements CommandInterface
{
	/**
	 * @var Logger
	 */
	private $logger;
    /**
     * AuthorizeAndCaptureCommand constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
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
                }

                else {
                    if(!empty($commandSubject['payment'])){
                        return null;
                    }
                    throw new LocalizedException(__('The order was not found.'));
                }

                $payment = $order->getPayment();

                if (!$order || !$order->getId()) {
                    throw new LocalizedException(__('The order no longer exists.'));
                }

                if (!empty($payment->getAdditionalInformation('transactionreference'))) {
                    $payment->setTransactionId($payment->getAdditionalInformation('transactionreference'));
                    $payment->setIsTransactionClosed(0);
                } else {
                    throw new LocalizedException(__('Transaction Reference was not found.'));
                }
                $this->logger->debug('--- ORDER INCREMENT ID: '. $order->getIncrementId() .'---');
                $this->logger->debug('--- AUTHORIZE ACTION ---');

                $payment->setAmountAuthorized($order->getTotalDue());

                $payment->setAdditionalInformation('is_complete', true);

                $payment = $payment->authorize(true, $order->getBaseTotalDue());

                $order->setState(Order::STATE_NEW);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW));
                $order->save();


        }
}
