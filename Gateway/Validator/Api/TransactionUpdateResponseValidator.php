<?php

namespace SecureTrading\Trust\Gateway\Validator\Api;

use Magento\Payment\Gateway\Validator\ResultInterface;
use SecureTrading\Trust\Gateway\Validator\AbstractResponseValidator;

/**
 * Class TransactionUpdateResponseValidator
 * @package SecureTrading\Trust\Gateway\Validator\Api
 */
class TransactionUpdateResponseValidator extends AbstractResponseValidator
{

	/**
	 * @param array $validationSubject
	 * @return ResultInterface
	 */
	public function validate(array $validationSubject)
	{
		if (empty($validationSubject['response'])) {
			throw new \InvalidArgumentException('Response does not exist');
		}

		$response = $validationSubject['response'];

		if ($this->getErrorCode($response)) {
			return $this->createResult(
				true,
				[]
			);
		} else {
			return $this->createResult(
				false,
				[__($this->getErrorMessage($response))]
			);
		}
	}

	/**
	 * @param $response
	 * @return bool
	 */
	public function getErrorCode($response){
		$this->logger->debug('--- Error Code: '.$response['errorcode'].' ---');
		if(isset($response['errorcode'])){
			if($response['errorcode'] == 0){
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * @param $response
	 * @return string
	 */
	public function getErrorMessage($response){
		$this->logger->debug('--- Error Message: '.$response['errormessage'].' ---');
		if(!empty($response['errormessage'])) {
			return $response['errormessage'];
		}
		return 'Something went wrong.';
	}

}
