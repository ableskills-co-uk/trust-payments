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


class RedirectRequest implements BuilderInterface
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

	function __construct(
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

	function build(array $buildSubject)
	{
		$order = $buildSubject['order'];
		$payment = $order->getPayment();
		$cancelUrl = $this->storeManager->getStore()->getBaseUrl().'checkout/cart';
		$returnUrl = $this->storeManager->getStore()->getBaseUrl().'securetrading/apisecuretrading/returnurlpaypal';


		$data['configData'] = array(
			'username' => $this->config->getValue(Data::USER_NAME),
			'password' => $this->config->getValue(Data::PASSWORD),
		);

		$mainAmount =  $this->subscriptionHelper->_formatMainAmount($order, $buildSubject);
		$baseAmount = !strpos($mainAmount,'.')
			? $mainAmount
			: (int)str_replace('.', '', $mainAmount);
		$data['requestData'] = array(
			'sitereference'           => $this->config->getValue(Data::SITE_REFERENCE),
			'requesttypedescriptions' => 'ORDER',
			'accounttypedescription'  => 'ECOM',
			'currencyiso3a'           => $this->subscriptionHelper->_getCurrencyCode($payment->getOrder()),
			'baseamount'              => (string)$baseAmount,
			'orderreference'          => $order->getIncrementId(),
			'paymenttypedescription'  => 'PAYPAL',
			'returnurl'               => $returnUrl,
			'cancelurl'               => $cancelUrl,
			'paypallocale'            => $this->locale->getLocale(),
			'paypaladdressoverride'   => '1',
			);

		$shippingAddress = [];

		if ($order->getShippingAddress()) {
			$shippingAddress =
				[
					'paypalemail'          => $order->getShippingAddress()->getEmail(),
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
					'customertelephone'    => $order->getShippingAddress()->getTelephone(),
					'customerpremise'      => '1'
				];
		}

		$data['requestData'] = array_merge($data['requestData'], $shippingAddress);

		if(is_array($subscriptionData = $this->subscriptionHelper->_processSubscriptionInRequest($payment->getOrder()))){
			$data['requestData'] = array_merge($data['requestData'], $subscriptionData);
		}

		$this->logger->debug('--- PREPARE DATA:', array($data));

		return $data;
	}
}
