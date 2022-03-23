<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Payment\Processor;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class AuthorizeAndCaptureCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class AuthorizeAndCaptureCommand implements CommandInterface
{
	/**
	 * @var Logger
	 */
	private $logger;
    /**
     * AuthorizeAndCaptureCommand constructor.
     * @param Logger $logger
     */
    function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }
    /**
     * @param array $commandSubject
     * @return void|null
     * @throws LocalizedException
     */
    function execute(array $commandSubject)
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
        $this->logger->debug('--- SETTLE STATUS AUTHORIZE AND CAPTURE : ' .$commandSubject['info']['settlestatus'].'---');
        if(!empty($commandSubject['info']['transactionreference'])){
            $payment->setTransactionId($commandSubject['info']['transactionreference']);
            if(isset($commandSubject['info']['parenttransactionreference'])){
				$payment->setParentTransactionId($commandSubject['info']['parenttransactionreference']);
			}else{
				$payment->setParentTransactionId($commandSubject['info']['transactionreference']);
			}
            $payment->setTransactionAdditionalInfo('transaction_data',$commandSubject['info']);
        }
        else {
            throw new LocalizedException(__('Transaction reference was not found.'));
        }

        if(isset($commandSubject['info']['settlestatus'])){
            if(in_array($commandSubject['info']['settlestatus'],[0,1,10])){

                $payment->setAmountAuthorized($order->getTotalDue());
                $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
                $payment->capture(null);

				$payment->setAdditionalInformation('is_complete', true);

                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus(Order::STATE_PROCESSING);
                $order->save();
            }
            else {
                throw new LocalizedException(__('Settle status is incorrect.'));
            }
        }
        else {
            throw new LocalizedException(__('Settle status was not found.'));
        }
    }
}