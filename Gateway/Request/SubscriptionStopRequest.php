<?php

namespace SecureTrading\Trust\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class SubscriptionStopRequest
 *
 * @package SecureTrading\Trust\Gateway\Request
 */
class SubscriptionStopRequest extends CancelRequest
{
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

		$data['cancel'] = [];
		$data['parenttransaction'] = [];
		if ($this->config->getValue(Data::BACK_OFFICE) == 0) {
			throw new \Magento\Framework\Exception\LocalizedException(__('Back-Office is required.'));
		}
		//Request stop subscription
		$data['cancel']['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$data['cancel']['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONUPDATE'),
			'filter'                  => array(
				'sitereference'        => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('nextrecurtransaction')))
			),
			'updates'                 => array('transactionactive' => '3')
		);
		//Request stop parent transaction
		$data['parenttransaction']['configData'] = $data['cancel']['configData'];

		$data['parenttransaction']['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONUPDATE'),
			'filter'                  => array(
				'sitereference'        => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('transactionreference')))
			),
			'updates'                 => array('settlestatus' => '3')
		);
		$this->logger->debug('--- ORDER INCREMENT ID: ' . $payment->getOrder()->getIncrementId() . '---');
		$this->logger->debug('--- PREPARE DATA TO STOP TRANSACTION REFERENCE : ', $data['parenttransaction']['requestData']);
		$this->logger->debug('--- PREPARE DATA TO STOP SUBSCRIPTION : ', $data['cancel']['requestData']);
		return $data;
	}
}