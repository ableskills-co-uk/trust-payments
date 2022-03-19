<?php

namespace SecureTrading\Trust\Gateway\Request\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\Logger\Logger;
use Magento\Framework\App\Area;
use SecureTrading\Trust\Helper\SubscriptionHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Locale\Resolver;


class AuthPayPalRequest implements BuilderInterface
{
	private $config;

	protected $storeManager;

	protected $enc;

	protected $logger;

	protected $state;

	protected $timezone;

	protected $priceCurrency;

	protected $subscriptionHelper;

	protected $store;

	protected $locale;

	public function __construct(
		PriceCurrencyInterface $priceCurrency,
		TimezoneInterface $timezone,
		ConfigInterface $config,
		StoreManagerInterface $storeManager,
		\Magento\Framework\Encryption\EncryptorInterface $enc,
		Logger $logger,
		\Magento\Framework\App\State $state,
		SubscriptionHelper $subscriptionHelper,
		StoreInterface $store,
		Resolver $locale
	) {
		$this->enc                = $enc;
		$this->storeManager       = $storeManager;
		$this->config             = $config;
		$this->logger             = $logger;
		$this->state 		      = $state;
		$this->timezone           = $timezone;
		$this->priceCurrency      = $priceCurrency;
		$this->subscriptionHelper = $subscriptionHelper;
		$this->store              = $store;
		$this->locale             = $locale;
	}

	public function build(array $buildSubject)
	{
		$reponseParams = $buildSubject['reponseParams'];

		$data['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$data['requestData'] = array(
			'requesttypedescriptions' => array('AUTH'),
			'sitereference' => $this->config->getValue(Data::SITE_REFERENCE),
			'parenttransactionreference' => $reponseParams['transactionreference'],
			'paymenttypedescription' => 'PAYPAL',
			'paypaladdressoverride' => '1',
			'paypalpayerid' => $reponseParams['PayerID']
		);

		$this->logger->debug('--- PREPARE DATA TO AUTH PAYPAL:', array($data));

		return $data;
	}
}
