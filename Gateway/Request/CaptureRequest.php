<?php

namespace SecureTrading\Trust\Gateway\Request;

use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Helper\SubscriptionHelper;


/**
 * Class CaptureRequest
 * @package SecureTrading\Trust\Gateway\Request
 */
class CaptureRequest implements BuilderInterface
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
	 * @var \Magento\Framework\App\State
	 */
	private $state;

	/**
	 * @var TimezoneInterface
	 */
	protected $timezone;

	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var PriceCurrencyInterface
	 */
	protected $priceCurrency;

	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;

	/**
	 * CaptureRequest constructor.
	 * @param ConfigInterface $config
	 * @param Logger $logger
	 * @param \Magento\Framework\App\State $state
	 * @param TimezoneInterface $timezone
	 * @param StoreManagerInterface $storeManager
	 * @param PriceCurrencyInterface $priceCurrency
	 * @param SubscriptionHelper $subscriptionHelper
	 */
	public function __construct(
        ConfigInterface $config,
        Logger $logger,
        \Magento\Framework\App\State $state,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
		SubscriptionHelper $subscriptionHelper
    ) {
        $this->logger             = $logger;
        $this->config             = $config;
        $this->state              = $state;
        $this->timezone           = $timezone;
        $this->storeManager       = $storeManager;
        $this->priceCurrency      = $priceCurrency;
        $this->subscriptionHelper = $subscriptionHelper;
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
        $data = [];

		if ($this->config->getValue(Data::BACK_OFFICE) == 0) {
			throw new \Magento\Framework\Exception\LocalizedException(__('Back-Office is required.'));
		}

        $data['configData'] = array(
            'username' => $this->config->getValue(Data::USER_NAME),
            'password' => $this->config->getValue(Data::PASSWORD),
        );
		$data['requestData'] = array(
			    'requesttypedescriptions' => array('TRANSACTIONUPDATE'),
			    'filter' => array(
				    'sitereference' => array(array('value' => $this->config->getValue(Data::SITE_REFERENCE))),
				    'transactionreference' => array(array('value' => $payment->getAdditionalInformation('transactionreference')))
			    ),
			    'updates' => array('settlestatus' => '0')
		    );
        $this->logger->debug('--- ORDER INCREMENT ID: '. $payment->getOrder()->getIncrementId() .'---');
        $this->logger->debug('--- PREPARE DATA TO CAPTURE:', $data['requestData']);
	    return $data;
    }
}