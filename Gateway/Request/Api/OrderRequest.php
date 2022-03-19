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


class OrderRequest implements BuilderInterface
{
	private $config;

	protected $storeManager;

	protected $enc;

	protected $logger;

	protected $state;

	protected $timezone;

	protected $priceCurrency;

	protected $subscriptionHelper;

	public function __construct(
		PriceCurrencyInterface $priceCurrency,
		TimezoneInterface $timezone,
		ConfigInterface $config,
		StoreManagerInterface $storeManager,
		\Magento\Framework\Encryption\EncryptorInterface $enc,
		Logger $logger,
		\Magento\Framework\App\State $state,
		SubscriptionHelper $subscriptionHelper
	) {
		$this->enc                = $enc;
		$this->storeManager       = $storeManager;
		$this->config             = $config;
		$this->logger             = $logger;
		$this->state 		      = $state;
		$this->timezone           = $timezone;
		$this->priceCurrency      = $priceCurrency;
		$this->subscriptionHelper = $subscriptionHelper;
	}

	public function build(array $buildSubject)
	{
		if (!isset($buildSubject['payment'])
			|| !$buildSubject['payment'] instanceof PaymentDataObjectInterface
		) {
			throw new \InvalidArgumentException('Payment data object should be provided');
		}

		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];

		$order = $paymentDO->getOrder();

		$payment = $paymentDO->getPayment();

		if (!$payment instanceof OrderPaymentInterface) {
			throw new \LogicException('Order payment should be provided.');
		}

		$data['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$expirydate = (string)$payment->getCcExpYear().'-'.(string)$payment->getCcExpMonth();
		$mainAmount =  $this->subscriptionHelper->_formatMainAmount($payment->getOrder(), $buildSubject);
		$baseAmount = !strpos($mainAmount,'.')
			? $mainAmount
			: (int)str_replace('.', '', $mainAmount);
		$data['requestData'] = array(
			'sitereference'           => $this->config->getValue(Data::SITE_REFERENCE),
			'requesttypedescriptions' => array('AUTH'),
			'accounttypedescription'  => $this->state->getAreaCode() == Area::AREA_ADMINHTML ? 'MOTO' : 'ECOM',
			'currencyiso3a'           => $this->subscriptionHelper->_getCurrencyCode($payment->getOrder()),
			'baseamount'              => (string)$baseAmount,
			'orderreference'          => $order->getOrderIncrementId(),
			'pan'                     => $payment->getAdditionalInformation('cc_number'),
			'expirydate'              => Date_format( Date_create($expirydate), "m/Y"),
			'securitycode'            => $payment->getAdditionalInformation('security_code'),

			'settleduedate'           => $this->subscriptionHelper->getSettleduedate($this->config->getValue(Data::API_SETTLE_DUE_DATE)),
			'settlestatus'            => $this->config->getValue(Data::API_PAYMENT_ACTION) == 'authorize' ? '2' : $this->config->getValue(Data::API_SETTLE_STATUS),

			'billingprefixname'   => $order->getBillingAddress()->getPrefix(),
			'billingfirstname'    => $order->getBillingAddress()->getFirstname(),
			'billingmiddlename'   => $order->getBillingAddress()->getMiddlename(),
			'billinglastname'     => $order->getBillingAddress()->getLastname(),
			'billingstreet'       => $order->getBillingAddress()->getStreetLine1(),
			'billingtown'         => $order->getBillingAddress()->getCity(),
			'billingcounty'       => $order->getBillingAddress()->getRegionCode(),
			'billingpostcode'     => $order->getBillingAddress()->getPostcode(),
			'billingcountryiso2a' => $order->getBillingAddress()->getCountryId(),
			'billingemail'        => $order->getBillingAddress()->getEmail(),
			'billingtelephone'    => $order->getBillingAddress()->getTelephone(),

			'successfulurlredirect'     => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/response',
			'declinedurlredirect'       => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/response',
			'declinedurlnotification'   => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/notificationdecline',
			'allurlnotification'        => 'https://webhook.site/5cb17533-2e2f-4fe4-b264-a4ae9f616d66?Multip',
			'ruleidentifier'           =>
				[
					'STR-6',
					'STR-7',
					'STR-9',
					'STR-10',
					'STR-11'
				],
			'url'          => 'https://payments.securetrading.net/process/payments/details',
			'customfield4' => 'MAGENTO',
			'customfield5' => $this->config->getVersionInformation(),
			'issubscription' => '0',
			'ismultishipping' => '0',
			'skipthefirstpayment' => '0',

			'stextraurlredirectfields' => ['isusediframe', 'ismultishipping','multishippingsetid'],
			'stextraurlnotifyfields'   =>
				[
					'accounttypedescription',
					'enrolled',
					'status',
					'maskedpan',
					'authcode',
					'securityresponsepostcode',
					'securityresponseaddress',
					'securityresponsesecuritycode',
					'expirydate',
					'issubscription',
					'ismultishipping',
					'multishippingsetid',
					'skipthefirstpayment'
				],
			'sitesecuritytimestamp'    => $this->subscriptionHelper->_formatTimeStamp(),);

		$shippingAddress = [];

		if ($order->getShippingAddress()) {
			$shippingAddress =
				[
					'customerprefixname'   => $order->getShippingAddress()->getPrefix(),
					'customerfirstname'    => $order->getShippingAddress()->getFirstname(),
					'customermiddlename'   => $order->getShippingAddress()->getMiddlename(),
					'customerlastname'     => $order->getShippingAddress()->getLastname(),
					'customerstreet'       => $order->getShippingAddress()->getStreetLine1(),
					'customertown'         => $order->getShippingAddress()->getCity(),
					'customercounty'       => $order->getShippingAddress()->getRegionCode(),
					'customerpostcode'     => $order->getShippingAddress()->getPostcode(),
					'customercountryiso2a' => $order->getShippingAddress()->getCountryId(),
					'customeremail'        => $order->getShippingAddress()->getEmail(),
					'customertelephone'    => $order->getShippingAddress()->getTelephone()
				];
		}

		$data['requestData'] = array_merge($data['requestData'], $shippingAddress);

		if(is_array($subscriptionData = $this->subscriptionHelper->_processSubscriptionInRequest($payment->getOrder()))){
			$data['requestData'] = array_merge($data['requestData'], $subscriptionData);
		}

		$sitesecurity         = $this->config->getSiteSecurity($data);
		$data['requestData']['sitesecurity'] = $sitesecurity;

		if (isset($payment->getAdditionalInformation()['save_card_info_api']) && $payment->getAdditionalInformation()['save_card_info_api'] == 1)
			$data['requestData']['credentialsonfile'] = '1';

		$this->logger->debug('--- ORDER INCREMENT ID: ' . $order->getOrderIncrementId() . '---');
		$this->logger->debug('--- PREPARE DATA:', array($data));

		return $data;
	}
}
