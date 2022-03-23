<?php

namespace SecureTrading\Trust\Gateway\Command\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Gateway\Command\AbstractCommand;


class CaptureCommand extends AbstractCommand
{
	/**
	 * @param array $commandSubject
	 * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
	 * @throws LocalizedException
	 */
	protected $serializer;

	function __construct(Registry $coreRegistry, BuilderInterface $requestBuilder, TransferFactoryInterface $transferFactory, Logger $logger, ConfigInterface $config, HandlerInterface $handler = null, ValidatorInterface $validator = null, ErrorMessageMapperInterface $errorMessageMapper = null, SerializerInterface $serializer)
	{
		parent::__construct($coreRegistry, $requestBuilder, $transferFactory, $logger, $config, $handler, $validator, $errorMessageMapper);
		$this->serializer = $serializer;
	}

	function execute(array $commandSubject)
	{
		$paymentPO = $commandSubject['payment'];
		if (!empty($paymentPO)) {
			$payment = $paymentPO->getPayment();
			$this->validateFreeTransaction($payment);


			$additionalData = $payment->getAdditionalInformation();

			//Process only for authorize payment action
			if($payment->getAdditionalInformation('payment_action') == "authorize" ){
				if($this->config->getValue(Data::BACK_OFFICE) == 1){
					if ($setId = $payment->getAdditionalInformation('multishipping_set_id')) {
						if ($this->coreRegistry->registry('is_not_current_order') != true) {
							$this->coreRegistry->register('is_not_current_order', true);
							$this->processCapture($commandSubject);
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
						$this->processCapture($commandSubject);
					}
				} else {
					throw new CommandException(__('Capture action requires back-office operation.')
					);
				}
			} else {
				return null;
			}
		}
	}

	function processCapture($commandSubject)
	{
		// @TODO implement exceptions catching
		//Send API to Update transaction
		//$response is an object
		$response = $this->transferFactory->create(
			$this->requestBuilder->build($commandSubject)
		);
		$responseDetails = $response->getSingle('responses')->getSingle(0)->getAll();
		//Validate error code
		if ($this->validator !== null) {
			$result = $this->validator->validate(
				array_merge($commandSubject, ['response' => $responseDetails])
			);
			if (!$result->isValid()) {
				$this->processErrors($result);
			}
		}
		//Change settle status
		if ($this->handler) {
			$this->handler->handle(
				$commandSubject,
				$responseDetails
			);
		}
	}

	function validateFreeTransaction($payment)
	{
		$skipthefirstpayment = $payment->getAdditionalInformation('skipthefirstpayment');
		$accounttypedescription = $payment->getAdditionalInformation('accounttypedescription');
		if ($skipthefirstpayment == 1 && $accounttypedescription == Data::RECUR_ACC_TYPE && $payment->getAdditionalInformation('subscriptiontype') == 'RECURRING') {
			throw new CommandException(__('Free transaction can\'t capture online, please capture offline'));
		}
	}
}