<?php


namespace SecureTrading\Trust\Gateway\Request\Api;


use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;

class DetailRequest implements BuilderInterface
{
	private $config;

	private $logger;

	public function __construct(
		ConfigInterface $config,
		Logger $logger
	) {
		$this->logger = $logger;
		$this->config = $config;
	}

	public function build(array $buildSubject)
	{
		$payment = $buildSubject['payment'];

		$data['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$data['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONQUERY'),
			'filter' => array(
				'sitereference' => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('transactionreference'))
				)
			)
		);
		$this->logger->debug('--- PREPARE DATA TO GET DETAIL :', $data['requestData']);
		return $data;
	}
}