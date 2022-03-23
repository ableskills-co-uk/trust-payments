<?php

namespace SecureTrading\Trust\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Model\MultiShippingFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Class UpdateAmountRequest
 * @package SecureTrading\Trust\Gateway\Request
 */
class UpdateAmountRequest implements BuilderInterface
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
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;

	/**
	 * @var SerializerInterface
	 */
	protected $serialize;

	/**
	 * @var CollectionFactory
	 */
	protected $collectionFactory;

	/**
	 * UpdateAmountRequest constructor.
	 * @param ConfigInterface $config
	 * @param Logger $logger
	 * @param MultiShippingFactory $multiShippingFactory
	 * @param SerializerInterface $serializer
	 * @param CollectionFactory $collectionFactory
	 */
	function __construct(
		ConfigInterface $config,
		Logger $logger,
		MultiShippingFactory $multiShippingFactory,
		SerializerInterface $serializer,
		CollectionFactory $collectionFactory
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->multiShippingFactory = $multiShippingFactory;
		$this->serialize = $serializer;
		$this->collectionFactory = $collectionFactory;
	}

	/**
	 * @param array $buildSubject
	 * @return array
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	function build(array $buildSubject)
	{
		if (!isset($buildSubject['payment'])
			|| !$buildSubject['payment'] instanceof PaymentDataObjectInterface
		) {
			throw new \InvalidArgumentException('Payment data object should be provided');
		}

		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];

		$payment = $paymentDO->getPayment();
		$data = [];
		if ($this->config->getValue(Data::BACK_OFFICE) == 0) {
			throw new \Magento\Framework\Exception\LocalizedException(__('Back-Office is required.'));
		}
		$data['update'] = [];
		$data['update']['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$data['update']['requestData'] = array(
			'requesttypedescriptions' => array('TRANSACTIONUPDATE'),
			'filter' => array(
				'sitereference' => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				'transactionreference' => array(array('value' => $payment->getAdditionalInformation('transactionreference')))
			),
			'updates' => array('settlebaseamount' => $this->getAmountAfterUpdate($payment))
		);

		if(!(int)$this->getAmountAfterUpdate($payment)>0){
			unset($data['update']['requestData']['updates']['settlebaseamount']);
			$data['update']['requestData']['updates']['settlestatus'] = '3';
		}

		$this->logger->debug('--- ORDER INCREMENT ID: '. $payment->getOrder()->getIncrementId() .'---');
		$this->logger->debug('--- PREPARE DATA TO UPDATE TRANSACTION:', $data['update']);
		return $data;
	}

	/**
	 * @param $payment
	 * @return mixed
	 */
	function getAmountAfterUpdate($payment)
	{
		if($payment['method'] == 'api_secure_trading' && isset($payment->getAdditionalInformation()['multishipping_data'])){
			$mainAmount = $payment->getAdditionalInformation()['multishipping_data']['mainamount'];
			$amountBaseGrandTotal = (int)$payment->getCreditmemo()->getBaseGrandTotal();
			$amountUpdate = number_format(($mainAmount - $amountBaseGrandTotal),2,'','');
		}else{
			$mainAmount = (int)$payment->getOrder()->getGrandTotal();
			$amountBaseGrandTotal = (int)$payment->getCreditmemo()->getBaseGrandTotal();
			$amountRefunded = (int)$payment->getAmountRefunded();
			$amountUpdate = (string)(($mainAmount - ($amountBaseGrandTotal+$amountRefunded))*100);
		}
		if ($payment['method'] == 'secure_trading' && isset($payment->getAdditionalInformation()['multishipping_data'])){
			$mainAmount = $payment->getAdditionalInformation()['multishipping_data']['mainamount'];
			$amountBaseGrandTotal = $payment->getCreditmemo()->getBaseGrandTotal();
			$amountUpdate = number_format(($mainAmount - $amountBaseGrandTotal),2,'','');
		}
		return $amountUpdate;
	}
}