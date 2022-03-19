<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Helper\Logger\Logger;
/**
 * Class RefundCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class RefundCommand extends AbstractCommand
{
    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * CaptureCommand constructor.
	 *
	 * @param Registry $coreRegistry
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param Logger $logger
     * @param HandlerInterface $handler
     * @param ValidatorInterface $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     * @param ConfigInterface $config
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
    	Registry $coreRegistry,
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        Logger $logger,
        ConfigInterface $config,
        CommandPoolInterface $commandPool,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    ) {
        parent::__construct($coreRegistry,$requestBuilder,$transferFactory,$logger,$config,$handler,$validator,$errorMessageMapper);
        $this->commandPool = $commandPool;
    }

    /**
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
     * @throws CommandException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute(array $commandSubject)
    {
        $data =  $this->requestBuilder->build($commandSubject);

        $transactionDetail = $this->transferFactory->create($data['detail']);
        $transactionDetailData = $transactionDetail->getSingle('responses')->getSingle(0)->getSingle('records')->getSingle(0)->getAll();
        $this->logger->debug('--- RESPONSE TRANSACTION DETAIL :', array($transactionDetailData));
        if(isset($transactionDetailData['settlestatus'])) {
            $this->logger->debug('--- RESPONSE SETTLE STATUS : ' . $transactionDetailData['settlestatus'] . ' ---');
            if ($transactionDetailData['settlestatus'] == 100) {
                $response = $this->transferFactory->create($data['refund']);
				$this->coreRegistry->register('is_settled',true);
                if ($this->coreRegistry->registry('refund_securetrading') != true)
                {
                    $this->acceptRefund($commandSubject,$response);
                    $this->coreRegistry->register('refund_securetrading',true);
                }
                else
                    $this->handlerRefund($commandSubject,$response);

            } elseif (in_array($transactionDetailData['settlestatus'], [0, 1, 10])) {
		            $this->updateTransaction($commandSubject);
            }
        } else {
            throw new CommandException(__('Can\'t refund this order.'));
        }
    }


    /**
     * @param $commandSubject
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @return void
     */
    protected function voidTransaction($commandSubject){
        $paymentPO = $commandSubject['payment'];
        $payment = $paymentPO->getPayment();
        $order   = $payment->getOrder();

        $this->commandPool->get('void')->execute($commandSubject);
        $order->addStatusHistoryComment(__('We voided the transaction because it has not settled yet.'));

        $order->save();

    }

	protected function updateTransaction($commandSubject){
		$paymentPO = $commandSubject['payment'];
		$payment = $paymentPO->getPayment();
		$order   = $payment->getOrder();

		$this->commandPool->get('update_amount')->execute($commandSubject);
		$order->addStatusHistoryComment(__('We have update the amount of transaction because it has not settled yet.'));

		$order->save();

	}

    /**
     * @param $commandSubject
     * @param $response
     * @throws CommandException
     */
    protected function acceptRefund($commandSubject, $response)
    {

        //Validate error code
        if ($this->validator !== null) {
            $result = $this->validator->validate(
                array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        $this->handlerRefund($commandSubject,$response);
    }

    /**
     * @param $commandSubject
     * @param $response
     */
    protected function handlerRefund($commandSubject, $response)
    {
        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                ['data' => $response['responses'][0]]
            );
        }
    }

}