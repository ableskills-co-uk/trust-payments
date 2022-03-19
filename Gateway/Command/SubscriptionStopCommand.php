<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use SecureTrading\Trust\Helper\Data;

/**
 * Class SubscriptionStopCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class SubscriptionStopCommand extends AbstractCommand
{
	/**
	 * @param array $commandSubject
	 * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
	 * @throws CommandException
	 */
	public function execute(array $commandSubject)
	{
		try {
			$data = $this->requestBuilder->build($commandSubject);
			$response = $this->transferFactory->create($data['cancel']);
			$this->logger->debug('--- REQUEST CANCEL SUBCRIPTION :', array($data['cancel']));
			//Validate error code
			$this->validator($response, $commandSubject);
			//Handel stop parent transaction
			$checkStatusData = $this->checkStatusTransaction($data);
			if ($checkStatusData['settlestatus'] != 100 && $checkStatusData['requesttypedescription'] == Data::AUTH_CHECK_TYPE) {
				$response = $this->transferFactory->create($data['parenttransaction']);
				//Validate error code
				$this->validator($response, $commandSubject);
			}
			//Change settle status
			if ($this->handler) {
				$this->handler->handle(
					$commandSubject,
					[]
				);
			}
		}catch (\Exception $e){
			$this->logger->debug('--- ERROR STOP SUBCRIPTION : ',array($e));
			throw new CommandException(__('Transaction has been declined. Please try again later.'));
		}
	}

	public function validator($response, $commandSubject)
	{
		if ($this->validator !== null) {
			$result = $this->validator->validate(
				array_merge($commandSubject, ['response' => $response])
			);
			if (!$result->isValid()) {
				$this->processErrors($result);
			}
		}
	}

	public function checkStatusTransaction($data)
	{
		$transactionDetail = $this->transferFactory->create($data['detailParent']);
		$transactionDetailData = $transactionDetail->getSingle('responses')->getSingle(0)->getSingle('records')->getSingle(0)->getAll();
		$this->logger->debug('--- RESPONSE TRANSACTION PARENT DETAIL :', array($transactionDetailData));
		return $transactionDetailData;
	}
}