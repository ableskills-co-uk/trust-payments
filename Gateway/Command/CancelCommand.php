<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use SecureTrading\Trust\Helper\Data;
/**
 * Class CancelCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class CancelCommand extends AbstractCommand
{
    /**
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
		$data = $this->requestBuilder->build($commandSubject);
		$paymentDO = $commandSubject['payment'];
    	$payment = $paymentDO->getPayment();
    	if($setId = $payment->getAdditionalInformation('multishipping_set_id')){
    		//Check settle status
    		if($this->coreRegistry->registry('is_not_current_order') != true){
				$transactionDetail     = $this->transferFactory->create($data['detail']);
				$transactionDetailData = $transactionDetail->getSingle('responses')->getSingle(0)->getSingle('records')->getSingle(0)->getAll();
				if (isset($transactionDetailData['settlestatus']) && in_array($transactionDetailData['settlestatus'], [0,2])) {
					$this->coreRegistry->registry('is_not_current_order',true);
					$this->processCancelation($commandSubject, $data['cancel']);
				  }
    		} else {
    			//Cancel related orders (Not send any request)
				if ($this->handler) {
					$this->handler->handle(
						$commandSubject,
						[]
					);
				}
			}
		} else {
			$this->processCancelation($commandSubject, $data['cancel']);
		}
    }

    /**
     * @param array $commandSubject
     * @param $data
     * @throws CommandException
     */
    public function processCancelation(array $commandSubject, $data){
		// @TODO implement exceptions catching
		//Send API to Update transaction
		//$response is an object
		$response = $this->transferFactory->create($data);
		//Validate error code
		if ($this->validator !== null) {
			$result = $this->validator->validate(
				array_merge($commandSubject, ['response' => $response])
			);
			if (!$result->isValid()) {
				$this->processErrors($result);
			}
		}
		//Change settle status
		if ($this->handler) {
			$this->handler->handle(
				$commandSubject,
				[]
			);
		}
	}
}