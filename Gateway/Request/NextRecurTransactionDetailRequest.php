<?php

namespace SecureTrading\Trust\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;


/**
 * Class NextRecurTransactionDetailRequest
 * @package SecureTrading\Trust\Gateway\Request
 */
class NextRecurTransactionDetailRequest implements BuilderInterface
{

	/**
	 * @var ConfigInterface
	 */
	private $config;
	/**
	 * @var Logger
	 */
	private $logger;
	/**
	 * NextRecurTransactionDetailRequest constructor.
	 * @param ConfigInterface $config
	 * @param Logger $logger
	 */
	public function __construct(
		ConfigInterface $config,
		Logger $logger
	) {
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @param array $buildSubject
	 * @return array
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	public function build(array $buildSubject)
	{
		if (!isset($buildSubject['payment'])
			|| !$buildSubject['payment'] instanceof PaymentDataObjectInterface
		) {
			throw new \InvalidArgumentException('Payment data object should be provided');
		}

		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];

		$payment = $paymentDO->getPayment();
		$data['detail'] = [];
		$data['detailParent'] = [];
		if ($this->config->getValue(Data::BACK_OFFICE) == 0) {
			throw new \Magento\Framework\Exception\LocalizedException(__('Back-Office is required.'));
		}
		$data['detail']['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$data['detail']['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONQUERY'),
			'filter' => array(
				'sitereference' => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('nextrecurtransaction')))
			)
		);

		$data['detailParent']['configData'] = $data['detail']['configData'];

		$data['detailParent']['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONQUERY'),
			'filter' => array(
				'sitereference' => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('transactionreference')))
			)
		);
		$this->logger->debug('--- ORDER INCREMENT ID: '. $payment->getOrder()->getIncrementId() .'---');
		$this->logger->debug('--- SEND DATA TO GET TRANSACTION DETAIL :', $data['detail']['requestData']);
		return $data;
	}
}