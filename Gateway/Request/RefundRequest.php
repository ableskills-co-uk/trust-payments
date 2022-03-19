<?php

namespace SecureTrading\Trust\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class RefundRequest
 * @package SecureTrading\Trust\Gateway\Request
 */
class RefundRequest implements BuilderInterface
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
     * RefundRequest constructor.
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
        $data['refund'] = [];
		if ($this->config->getValue(Data::BACK_OFFICE) == 0) {
			throw new \Magento\Framework\Exception\LocalizedException(__('Back-Office is required.'));
		}
        $data['refund']['configData'] = array(
            'username' => $this->config->getValue(Data::USER_NAME),
            'password' => $this->config->getValue(Data::PASSWORD),
        );

        $data['refund']['requestData'] = array(
            'requesttypedescriptions' => array('REFUND'),
            'sitereference' =>$this->config->getValue(Data::SITE_REFERENCE),
            'parenttransactionreference' => $payment->getAdditionalInformation('transactionreference'),
            //Format amount (remove comma, dot)
            'baseamount' => number_format($payment->getCreditmemo()->getBaseGrandTotal(),2,'','')
        );
        $this->logger->debug('--- ORDER INCREMENT ID: '. $payment->getOrder()->getIncrementId() .'---');
        $this->logger->debug('--- PREPARE DATA TO REFUND :', $data['refund']['requestData']);
        return $data;
    }
}