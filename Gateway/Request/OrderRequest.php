<?php

namespace SecureTrading\Trust\Gateway\Request;

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

/**
 * Class OrderRequest
 *
 * @package SecureTrading\Trust\Gateway\Request
 */
class OrderRequest implements BuilderInterface
{
	/**
	 * @var ConfigInterface
	 */
	private $config;

	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var \Magento\Framework\Encryption\EncryptorInterface
	 */
	protected $enc;

	/**
	 * @var \SecureTrading\Trust\Helper\Logger\Logger
	 */
	protected $logger;

	/**
	 * @var \Magento\Framework\App\State
	 */
	protected $state;

	/**
	 * @var TimezoneInterface
	 */
	protected $timezone;

	/**
	 * @var PriceCurrencyInterface
	 */
	protected $priceCurrency;

	/**
	 * OrderRequest constructor.
	 *
	 * @param PriceCurrencyInterface $priceCurrency
	 * @param TimezoneInterface $timezone
	 * @param ConfigInterface $config
	 * @param StoreManagerInterface $storeManager
	 * @param \Magento\Framework\Encryption\EncryptorInterface $enc
	 * @param Logger $logger
	 * @param \Magento\Framework\App\State $state
	 */
	function __construct(
		PriceCurrencyInterface $priceCurrency,
		TimezoneInterface $timezone,
		ConfigInterface $config,
		StoreManagerInterface $storeManager,
		\Magento\Framework\Encryption\EncryptorInterface $enc,
		Logger $logger,
		\Magento\Framework\App\State $state
	) {
		$this->enc          = $enc;
		$this->storeManager = $storeManager;
		$this->config       = $config;
		$this->logger       = $logger;
		$this->state 		= $state;
		$this->timezone     = $timezone;
		$this->priceCurrency= $priceCurrency;
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

		$order = $paymentDO->getOrder();

		$payment = $paymentDO->getPayment();

		if (!$payment instanceof OrderPaymentInterface) {
			throw new \LogicException('Order payment should be provided.');
		}
		$url = $this->config->getValue(
			Data::DETAILS_PAGE
		);

		if (!$this->config->getValue(Data::SKIP_CHOICE_PAGE)) {
			$url = $this->config->getValue(
				Data::CHOICE_PAGE
			);
		}
		$data = [
			'sitereference' => $this->config->getValue(Data::SITE_REFERENCE),
			'currencyiso3a' => $this->_getCurrencyCode($payment->getOrder()),
			'mainamount'    => $this->_formatMainAmount($payment->getOrder(), $buildSubject),
//			'mainamount'    => strval(600.10), //todo: why round ?
			'version'       => $this->config->getValue(Data::VERSION),
			'stprofile'     => $this->config->getValue(Data::ST_PROFILE),

			//Customer will be offered a choice between the delivery address entered on
			//your website and addresses on their PayPal account.
			'paypaladdressoverride' => 0,

            'billingpremise'      => $order->getBillingAddress()->getStreetLine1(),
			'billingprefixname'   => $order->getBillingAddress()->getPrefix(),
			'billingfirstname'    => $order->getBillingAddress()->getFirstname(),
			'billingmiddlename'   => $order->getBillingAddress()->getMiddlename(),
			'billinglastname'     => $order->getBillingAddress()->getLastname(),
			'billingstreet'       => $order->getBillingAddress()->getStreetLine2(),
			'billingtown'         => $order->getBillingAddress()->getCity(),
			'billingcounty'       => $order->getBillingAddress()->getRegionCode(),
			'billingpostcode'     => $order->getBillingAddress()->getPostcode(),
			'billingcountryiso2a' => $order->getBillingAddress()->getCountryId(),
			'billingemail'        => $order->getBillingAddress()->getEmail(),
			'billingtelephone'    => $order->getBillingAddress()->getTelephone(),

			'settleduedate' => $this->getSettleduedate($this->config->getValue(Data::SETTLE_DUE_DATE)),
			'settlestatus'  => $this->config->getValue(Data::PAYMENT_ACTION) == 'authorize' ? 2 : $this->config->getValue(Data::SETTLE_STATUS),

			'orderreference'            => $order->getOrderIncrementId(),
			'successfulurlredirect'     => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/response',
			'declinedurlredirect'       => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/response',
//			'successfulurlnotification' => 'https://webhook.site/5b89dc20-9d3f-40e4-8f93-246ef5263ba4',
			'successfulurlnotification' => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/notificationresponse',
			'declinedurlnotification'   => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/notificationdecline',
			'allurlnotification'        => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/notificationall',
			'ruleidentifier'           =>
				[
					'STR-6',
					'STR-7',
					'STR-8',
					'STR-9',
					'STR-10',
					'STR-11'
				],
			'url'                       => $url,
			'accounttypedescription'    => $this->state->getAreaCode() == Area::AREA_ADMINHTML ? 'MOTO' : 'ECOM',

			'customfield4' => $this->_getCartInformation(),
			'customfield5' => $this->config->getVersionInformation(),
			'isusediframe' => $this->config->getValue(Data::USE_IFRAME),
			'issubscription' => 0,
			'ismultishipping' => 0,
			'skipthefirstpayment' => 0,

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
					'skipthefirstpayment',
                    'customerpremise',
					'customerprefixname',
					'customerfirstname',
					'customermiddlename',
					'customerlastname',
					'customerstreet',
					'customertown',
					'customercounty',
					'customerpostcode',
					'customercountryiso2a',
					'customeremail',
					'customertelephone',
                    'billingpremise',
					'billingprefixname',
					'billingfirstname',
					'billingmiddlename',
					'billinglastname',
					'billingstreet',
					'billingtown',
					'billingcounty',
					'billingpostcode',
					'billingcountryiso2a',
					'billingemail',
					'billingtelephone',
				],
//			'sitesecuritytimestamp'    => date('Y-m-d h:i:s', time()),
			'sitesecuritytimestamp'    => $this->_formatTimeStamp(),
		];

		$shippingAddress = [];

		if ($order->getShippingAddress()) {
			$shippingAddress =
				[
                    'customerpremise'      => $order->getBillingAddress()->getStreetLine1(),
					'customerprefixname'   => $order->getShippingAddress()->getPrefix(),
					'customerfirstname'    => $order->getShippingAddress()->getFirstname(),
					'customermiddlename'   => $order->getShippingAddress()->getMiddlename(),
					'customerlastname'     => $order->getShippingAddress()->getLastname(),
					'customerstreet'       => $order->getShippingAddress()->getStreetLine2(),
					'customertown'         => $order->getShippingAddress()->getCity(),
					'customercounty'       => $order->getShippingAddress()->getRegionCode(),
					'customerpostcode'     => $order->getShippingAddress()->getPostcode(),
					'customercountryiso2a' => $order->getShippingAddress()->getCountryId(),
					'customeremail'        => $order->getShippingAddress()->getEmail(),
					'customertelephone'    => $order->getShippingAddress()->getTelephone()
				];
		}

		$data = array_merge($data, $shippingAddress);

		$this->logger->debug('--- ORDER INCREMENT ID: ' . $order->getOrderIncrementId() . '---');
		$this->logger->debug('--- PREPARE DATA:', array($data));

		if(is_array($subscriptionData = $this->_processSubscription($payment->getOrder()))){
				$data = array_merge($data, $subscriptionData);
		}

		$sitesecurity         = $this->config->getSiteSecurity($data);
		$data['sitesecurity'] = $sitesecurity;

        if (isset($payment->getAdditionalInformation()['save_card_info']) && $payment->getAdditionalInformation()['save_card_info'] == 1){
            $data['credentialsonfile'] = 1;
        }elseif ($payment->getMethod() == "vault_secure_trading"){
            $data['credentialsonfile'] = 2;
        }

        if ($this->storeManager->getStore()->getConfig(Data::IS_TOKENIZATION) == 1 && $this->config->getValue('jwt_name') && $this->config->getValue('jwt_secret_key'))
        {
            $data['jwt_name'] = $this->config->getValue('jwt_name');
            $data['jwt_secret_key'] = $this->config->getValue('jwt_secret_key');
            $this->logger->addDebug($data['jwt_secret_key']);
        }

		return $data;
	}

	/**
	 * @param $settleDueDate
	 * @return false|string
	 */
	protected function getSettleduedate($settleDueDate)
	{
		$settleDueDate = (int)$settleDueDate;
		$daysToAdd     = '+ ' . $settleDueDate . ' days';
		//todo: need to handle return = false
		return $formattedSettleDueDate = date('Y-m-d', strtotime($daysToAdd));
	}

    /**
     * @return string
     */
    protected function _getCartInformation()
	{
		return 'MAGENTO';
	}

    /**
     * @param $order
     * @return mixed
     */
    protected function _getCurrencyCode($order)
	{
		return $order->getBaseCurrencyCode();
	}

    /**
     * @param $order
     * @param array $buildSubject
     * @return string
     */
    protected function _formatMainAmount($order, array $buildSubject)
	{
		if ($this->_getCurrencyCode($order) == 'JPY') {
			return strval(number_format(SubjectReader::readAmount($buildSubject), 0, '', ''));
		}
		return strval(number_format(SubjectReader::readAmount($buildSubject), 2, '.', ''));
	}

    /**
     * @param $order
     * @return array|null
     */
    protected function _processSubscription($order){
		$items = $order->getItems();
		$data = [];
		foreach($items as $item){
			$options = $item->getProductOptions();
			if(isset($options["secure_trading_subscription"])){
				foreach($options["secure_trading_subscription"] as $key => $value){
					if($key == 'skipthefirstpayment' && $value == 1){
						$data["orderedrequesttypedescriptions"] = "THREEDQUERY,ACCOUNTCHECK,SUBSCRIPTION";
						$data["skipthefirstpayment"] = 1;
					} else {
						$data[$key] = $value;
					}
				}
				$data["settlestatus"] = $this->config->getValue(Data::SETTLE_STATUS);
				$data["issubscription"] = 1;
				$data["credentialsonfile"] = 1;
				//Handle installment main amount
				if ($data["subscriptiontype"] == 'INSTALLMENT') {
					//Divide main amount
					if($data["skipthefirstpayment"] == 1){
						$realFinalNumber = (int)$data["subscriptionfinalnumber"] - 1;
					} else {
						$realFinalNumber = (int)$data["subscriptionfinalnumber"];
					}
					$buildAmount["amount"] = $this->priceCurrency->round((float)($order->getBaseGrandTotal() / $realFinalNumber));
					$data["mainamount"]    = $this->_formatMainAmount($order, $buildAmount);
				}

				return $data;
			}
		}
		return null;
	}

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _formatTimeStamp()
	{
		$date = $this->timezone->date();
		$formattedDate  = $this->timezone->convertConfigTimeToUtc($date);
		return $formattedDate;
	}
}
