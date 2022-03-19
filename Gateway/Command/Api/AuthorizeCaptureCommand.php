<?php

namespace SecureTrading\Trust\Gateway\Command\Api;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Helper\Logger\Logger;

class AuthorizeCaptureCommand implements CommandInterface
{
	protected $handler;

	protected $requestBuilder;

	protected $transferFactory;

	protected $logger;

	protected $validator;

	protected $errorMessageMapper;

	public function __construct(
		BuilderInterface $requestBuilder,
		HandlerInterface $handler,
		TransferFactoryInterface $transferFactory,
		Logger $logger,
		ValidatorInterface $validator = null,
		ErrorMessageMapperInterface $errorMessageMapper = null
	) {
		$this->handler             = $handler;
		$this->requestBuilder      = $requestBuilder;
		$this->transferFactory     = $transferFactory;
		$this->logger              = $logger;
		$this->validator           = $validator;
		$this->errorMessageMapper  = $errorMessageMapper;
	}


	public function execute(array $commandSubject)
	{
		$data =  $this->requestBuilder->build($commandSubject);
		$response = $this->transferFactory->create($data);
		$transactionDetailData = $response->getSingle('responses')->getSingle(0)->getAll();
		$this->logger->debug('--- RESPONSE TRANSACTION DETAIL :', array($transactionDetailData));

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
				$transactionDetailData
			);
		}
	}

	protected function processErrors(ResultInterface $result)
	{
		$messages = [];
		$errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());
		foreach ($errorsSource as $errorCodeOrMessage) {
			$errorCodeOrMessage = (string) $errorCodeOrMessage;

			// error messages mapper can be not configured if payment method doesn't have custom error messages.
			if ($this->errorMessageMapper !== null) {
				$mapped = (string) $this->errorMessageMapper->getMessage($errorCodeOrMessage);
				if (!empty($mapped)) {
					$messages[] = $mapped;
					$errorCodeOrMessage = $mapped;
				}
			}
			$this->logger->debug('Payment Error: ' . $errorCodeOrMessage);
		}

		throw new CommandException(
			!empty($messages)
				? __(implode(PHP_EOL, $messages))
				: __('Transaction has been declined. Please try again later.')
		);
	}
}