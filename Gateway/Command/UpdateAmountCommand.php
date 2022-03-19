<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Helper\Data;
use Magento\Payment\Gateway\Http\ClientInterface;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class UpdateAmountCommand
 * @package SecureTrading\Trust\Gateway\Command
 */
class UpdateAmountCommand extends AbstractCommand
{
	/**
	 * @param array $commandSubject
	 * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
	 * @throws CommandException
	 */
	public function execute(array $commandSubject)
	{
		$data = $this->requestBuilder->build($commandSubject);
		$this->processUpdate($commandSubject, $data['update']);
	}
	/**
	 * @param array $commandSubject
	 * @param $data
	 * @throws CommandException
	 */
	public function processUpdate(array $commandSubject, $data)
	{
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
		if ($this->handler) {
			$this->handler->handle(
				$commandSubject,
				[]
			);
		}
	}
}