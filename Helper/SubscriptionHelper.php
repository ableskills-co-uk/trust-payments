<?php

namespace SecureTrading\Trust\Helper;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use SecureTrading\Trust\Model\Source\Type;
use SecureTrading\Trust\Model\Source\Unit;
use SecureTrading\Trust\Model\SubscriptionFactory;
use SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory;
use Magento\Framework\Pricing\Helper\Data as PriceFormat;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Module\FullModuleList;

/**
 * Class SubscriptionHelper
 *
 * @package SecureTrading\Trust\Helper
 */
class SubscriptionHelper
{
	/**
	 * @var OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var PaymentFactory
	 */
	protected $paymentFactory;

	/**
	 * @var AddressFactory
	 */
	protected $addressFactory;

	/**
	 * @var SubscriptionFactory
	 */
	protected $subscriptionFactory;

	/**
	 * @var CollectionFactory
	 */
	protected $subsCollectionFactory;

	/**
	 * @var PriceFormat
	 */
	protected $priceFormat;
	/**
	 * @var array
	 */
	protected $transferDataKeys = [
		'store_id',
		'store_name',
		'customer_id',
		'customer_email',
		'customer_firstname',
		'customer_lastname',
		'customer_middlename',
		'customer_prefix',
		'customer_suffix',
		'customer_taxvat',
		'customer_gender',
		'customer_is_guest',
		'customer_note_notify',
		'customer_group_id',
		'customer_note',
		'shipping_method',
		'shipping_description',
		'base_currency_code',
		'global_currency_code',
		'order_currency_code',
		'store_currency_code',
		'base_to_global_rate',
		'base_to_order_rate',
		'store_to_base_rate',
		'store_to_order_rate'
	];

	protected $subscriptionData = [
		'orderreference'          => 'order_id',
		'subscriptionunit'        => 'unit',
		'subscriptionfrequency'   => 'frequency',
		'subscriptionfinalnumber' => 'final_number',
		'subscriptiontype'        => 'type',
	];
	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var ScopeConfigInterface
	 */
	protected $scopeConfig;


	/**
	 * @var State
	 */
	protected $state;

	/**
	 * @var PaymentTokenFactoryInterface
	 */
	protected $paymentTokenFactory;

	/**
	 * @var Json
	 */
	protected $json;

	protected $timezone;

	protected $priceCurrency;

	protected $fullModuleList;

	function __construct(OrderFactory $orderFactory,
	                            PaymentFactory $paymentFactory,
	                            AddressFactory $addressFactory,
	                            SubscriptionFactory $subscriptionFactory,
	                            PriceFormat $priceFormat,
	                            CollectionFactory $collectionFactory,
	                            StoreManagerInterface $storeManager,
	                            ScopeConfigInterface $scopeConfig,
	                            State $state,
	                            PaymentTokenFactoryInterface $paymentTokenFactory,
	                            Json $json,
	                            TimezoneInterface $timezone,
	                            PriceCurrencyInterface $priceCurrency,
	                            FullModuleList $fullModuleList
								)
	{
		$this->orderFactory          = $orderFactory;
		$this->paymentFactory        = $paymentFactory;
		$this->addressFactory        = $addressFactory;
		$this->subscriptionFactory   = $subscriptionFactory;
		$this->priceFormat           = $priceFormat;
		$this->subsCollectionFactory = $collectionFactory;
		$this->storeManager          = $storeManager;
		$this->scopeConfig           = $scopeConfig;
		$this->state                 = $state;
		$this->paymentTokenFactory   = $paymentTokenFactory;
		$this->json                  = $json;
		$this->timezone              = $timezone;
		$this->priceCurrency         = $priceCurrency;
		$this->fullModuleList        = $fullModuleList;
	}

	/**
	 * Create new order (subscription)
	 *
	 * @param $parentOrder
	 * @return Order
	 */
	function createOrder($parentOrder)
	{
		if (!empty($parentOrder)) {
			$order     = $parentOrder;
			$newOrder  = $this->orderFactory->create();
			$orderInfo = $order->getData();
			try {
				$billingAdd  = $this->addressFactory->create();
				$billingInfo = $order->getBillingAddress()->getData();
				$billingAdd->setData($billingInfo)->setId(null);

				if ($order->getShippingAddress()) {
					$shippingAdd  = $this->addressFactory->create();
					$shippingInfo = $order->getBillingAddress()->getData();
					$shippingAdd->setData($shippingInfo)->setId(null);
				} else {
					$shippingAdd = null;
				}
				/** @var \Magento\Sales\Model\Order\Payment $payment */
				$payment           = $this->paymentFactory->create();
				$paymentMethodCode = $order->getPayment()->getMethod();
				$subsId            = $order->getPayment()->getAdditionalInformation('subscriptionid');
				$payment->setAdditionalInformation('subscriptionid',$subsId);
				$payment->setMethod($paymentMethodCode);

				foreach ($this->transferDataKeys as $key) {
					if (isset($orderInfo[$key])) {
						$newOrder->setData($key, $orderInfo[$key]);
					} elseif (isset($shippingInfo[$key])) {
						$newOrder->setData($key, $shippingInfo[$key]);
					}
				}

				$newOrder->setStoreId($order->getStoreId())
					->setState(Order::STATE_NEW)
					->setStatus(Data::ORDER_STATUS)
					->setBaseToOrderRate($order->getBaseToOrderRate())
					->setStoreToOrderRate($order->getStoreToOrderRate())
					->setOrderCurrencyCode($order->getOrderCurrencyCode())
					->setBaseSubtotal($order->getBaseSubtotal())
					->setSubtotal($order->getSubtotal())
					->setBaseShippingAmount($order->getBaseShippingAmount())
					->setShippingAmount($order->getShippingAmount())
					->setBaseTaxAmount($order->getBaseTaxAmount())
					->setTaxAmount($order->getTaxAmount())
					->setBaseGrandTotal($order->getBaseGrandTotal())
					->setGrandTotal($order->getGrandTotal())
					->setIsVirtual($order->getIsVirtual())
					->setWeight($order->getWeight())
					->setBillingAddress($billingAdd)
					->setShippingAddress($shippingAdd)
					->setPayment($payment);

				/** @var \Magento\Sales\Model\Order\Item[] $items */
				$items = $order->getAllItems();
				foreach ($items as $item) {
					$newOrderItem = clone $item;
					$newOrderItem->setId(null);
					$newOrderItem->setQtyShipped(0);
					$newOrderItem->setQtyInvoiced(0);
					$newOrder->addItem($newOrderItem);
				}

				return $newOrder;
			} catch (\Exception $e) {
			}
		}
	}

	/**
	 * Add subscription via webhook
	 *
	 * @param $newOrder
	 * @param $subscriptionData
	 * @throws \Exception
	 */
	function addSubscriptionItem($newOrder, $subscriptionData)
	{
		if (!empty($subscriptionData['transactionreference'])) {
			$subscription       = $this->subscriptionFactory->create()->loadByTransactionId($subscriptionData['transactionreference']);
			$parentSubscription = $this->subscriptionFactory->create()->loadByTransactionId($subscriptionData['parenttransactionreference']);
			if (!$subscription->getId() && $parentSubscription->getStatus() == 0) {
				$data                          = [];
				$data['parent_order_id']       = $subscriptionData['orderreference'];
				$data['parent_transaction_id'] = $subscriptionData['parenttransactionreference'];
				$data['order_id']              = $newOrder->getIncrementId();
				$data['transaction_id']        = $subscriptionData['transactionreference'];
				$data['number']                = $subscriptionData['subscriptionnumber'];
				$item                          = $this->subscriptionFactory->create();
				$item->setData($data)->save();
				$this->isComplete($parentSubscription, $subscriptionData);
			}
		}
	}

	/**
	 * Check if a subscription is complete
	 *
	 * @param $parentSubscription
	 * @param $subscriptionData
	 */
	function isComplete($parentSubscription, $subscriptionData)
	{
		if (!empty($parentSubscription->getId())) {
			$numberOfSubscription = $this->subsCollectionFactory->create()
				->addFieldToFilter('parent_order_id', $subscriptionData['orderreference'])
				->addFieldToFilter('parent_transaction_id', $subscriptionData['parenttransactionreference'])
				->count('*');
			if ($numberOfSubscription == (int)$subscriptionData['subscriptionnumber'] &&
				$subscriptionData['subscriptionnumber'] == $parentSubscription->getFinalNumber()) {
				$parentSubscription->setStatus(1);
				$parentSubscription->save();
			}

		}
	}

	/**
	 * @param $priceProduct
	 * @param int $subscriptionFinalNumber
	 * @param int $skipTheFirstPayment
	 * @param $style
	 * @return float|int|string
	 */
	function getValue($priceProduct, int $subscriptionFinalNumber, int $skipTheFirstPayment, $style)
	{
		$price = 0;
		if ($priceProduct) {
			if ($style === "INSTALLMENT") {
				$price = (float)($priceProduct / ($subscriptionFinalNumber - $skipTheFirstPayment));
			} else if ($style === "RECURRING") {
				$price = (float)($priceProduct);
			}
		}
		$price = $this->priceFormat->currency($price, true, false);
		return $price;
	}

	/**
	 * @param int $skipTheFirstPayment
	 * @param int $frequency
	 * @param $unit
	 * @param int $finalNumber
	 * @param $priceProduct
	 * @param $style
	 * @return string
	 */
	function getDescription(int $skipTheFirstPayment, int $frequency, $unit, int $finalNumber, $priceProduct, $style){
		$stylePrice = $style;
		$unit = strtolower($unit);
//		$unit = strtoupper(substr($unit,0,1)).substr($unit,1);
		$style = strtolower($style);
//		$style = strtoupper(substr($style,0,1)).substr($style,1);
		if ($skipTheFirstPayment) {
			return $this->getValue($priceProduct, $finalNumber, $skipTheFirstPayment, $stylePrice) . ' every ' . $frequency . ' ' . $unit . '(s) processing ' . $finalNumber . ' ' . $style .' payments in total (free trial - first ' . strtolower($unit) . ' is free.)';
		} else {
			return $this->getValue($priceProduct, $finalNumber, $skipTheFirstPayment, $stylePrice) . ' every ' . $frequency . ' ' . $unit . '(s) processing ' . $finalNumber . ' ' .$style .' payments in total';
		}
	}

	/**
	 * @return mixed
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	function getCurrentCurrencyCode()
	{
		return $this->storeManager->getStore()->getCurrentCurrencyCode();
	}

	/**
	 * @return mixed
	 */
	function getSitereference()
	{
		return $this->scopeConfig->getValue('payment/secure_trading/site_reference', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	/**
	 * @return mixed
	 */
	function getAnimatedCard()
	{
		return $this->scopeConfig->getValue('payment/api_secure_trading/animated_card', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	/**
	 * @return string
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	function getAccountTypeDescription()
	{
		return $this->state->getAreaCode() == Area::AREA_ADMINHTML ? 'MOTO' : 'ECOM';
	}

	/**
	 * @param $paymentTypeDescription
	 * @return mixed
	 */
	function getPaymentType($paymentTypeDescription)
	{
		$listType = [
			'VISA'       => 'VI',
			'MASTERCARD' => 'MC',
		];

		key_exists($paymentTypeDescription,$listType) ?
			$paymentType = $listType[$paymentTypeDescription] : $paymentType = $paymentTypeDescription;

		return $paymentType;
	}

	/**
	 * @param $cardExpire
	 * @return array
	 */
	function getCardExpire($cardExpire)
	{
		return isset($cardExpire) ? array(explode("/",$cardExpire)) : array();
	}

	/**
	 * @param $parentTransaction
	 * @return string
	 */
	function genPublicHash($parentTransaction)
	{
		return base64_encode($parentTransaction);
	}

	/**
	 * @param $response
	 * @param $order
	 * @param $payment
	 */
	function saveCreditCard($response, $payment, $order){

		$paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

		$cardDigits = $response['maskedpan'];
		$accountType = $response['accounttypedescription'];
		$paymentType = $this->getPaymentType($response['paymenttypedescription']);

		$parentTrans = $response['transactionreference'];
		$publichash = $this->genPublicHash($response['transactionreference']);
		$customerId = $order->getCustomerId();

		$detail = $this->json->serialize([
			"maskedpan"     => substr($cardDigits,-4),
			"cardExpire"    => $this->getCardExpire($response['expirydate']),
			"accountType"   => $accountType,
			"paymentType"   => $paymentType,
			"payment_action"=> $payment->getAdditionalInformation('payment_action'),
			"parenttransactionreference" => $parentTrans
		]);

		$paymentToken->setCustomerId($customerId);
		$paymentToken->setGatewayToken($parentTrans);
		$paymentToken->setTokenDetails($detail);
		$paymentToken->setExpiresAt(strtotime('+1 year'));
		$paymentToken->setPaymentMethodCode($payment->getMethod());
		$paymentToken->setPublicHash($publichash);
		$paymentToken->save();
	}

	function processSubscription($payment, $responseParams, $optionSubs)
	{
		$data = [];
		$additional_information = $payment->getAdditionalInformation();
		$transactionreference = (isset($responseParams['transactionreference'])) ? $responseParams['transactionreference'] : $additional_information['transactionreference'];
		$orderreference = (isset($responseParams['orderreference'])) ? $responseParams['orderreference'] : $additional_information['orderreference'];

			if (isset($optionSubs['secure_trading_subscription'])) {
				foreach ($optionSubs['secure_trading_subscription'] as $key => $value) {
					if (isset($this->subscriptionData[$key])) {
						$data[$this->subscriptionData[$key]] = $value;
					}
				}
			}
			if (isset($transactionreference)) {
				$data['transaction_id'] = $transactionreference;
				$data['parent_transaction_id'] = $transactionreference;
			}
			if(isset($orderreference)){
				$data['parent_order_id'] = $orderreference;
				$data['order_id'] = $orderreference;
			}
			$data['type'] = $this->convertType($data['type']);
			$data['unit'] = $this->convertUnit($data['unit']);
			$data['skip_the_first_payment'] = isset($responseParams['skipthefirstpayment'])? $responseParams['skipthefirstpayment'] : 0;
			$data['status'] = 0;
			$data['number'] = 1;
			$subscription   = $this->subscriptionFactory->create();
			if( empty($subscription->load($transactionreference,'transaction_id')->getData()))
			{
				$subscription->setData($data);
				$subscription->save();
			}
	}

	protected function convertType($type)
	{
		return array_search($type, Type::getOptionArray());
	}

	protected function convertUnit($unit)
	{
		return array_search($unit,Unit::getOptionArray());
	}

	function formatMainAmount($order, $baseAmount)
	{
		if ($this->_getCurrencyCode($order) == 'JPY') {
			return strval(number_format($baseAmount, 0, '', ''));
		}
		return strval(number_format($baseAmount, 2, '.', ''));
	}

	function _formatMainAmount($order, array $buildSubject)
	{
		if ($this->_getCurrencyCode($order) == 'JPY') {
			return strval(number_format(SubjectReader::readAmount($buildSubject), 0, '', ''));
		}
		return strval(number_format(SubjectReader::readAmount($buildSubject), 2, '.', ''));
	}

	function _getCurrencyCode($order)
	{
		return $order->getBaseCurrencyCode();
	}

	function getSettleduedate($settleDueDate)
	{
		$settleDueDate = (int)$settleDueDate;
		$daysToAdd     = '+ ' . $settleDueDate . ' days';
		//todo: need to handle return = false
		return $formattedSettleDueDate = date('Y-m-d', strtotime($daysToAdd));
	}

	function _processSubscriptionInRequest($order){
		$items = $order->getItems();
		$data = [];
		foreach($items as $item){
			$options = $item->getProductOptions();
			if(isset($options["secure_trading_subscription"])){
				$data['requesttypedescriptions'] = array('AUTH','SUBSCRIPTION');
				foreach($options["secure_trading_subscription"] as $key => $value){
					if($key == 'skipthefirstpayment' && $value == 1){
						$data["requesttypedescriptions"] = [
							"ACCOUNTCHECK", "SUBSCRIPTION"
						];
						$data["skipthefirstpayment"] = 1;
					} else {
						$data[$key] = $value;
					}
				}
				$data["issubscription"] = '1';
				$data["credentialsonfile"] = '1';
				$data['subscriptionnumber'] = '1';
				$data["settlestatus"] = $this->scopeConfig->getValue('payment/api_secure_trading/api_settle_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				//Handle installment main amount
				if ($data["subscriptiontype"] == 'INSTALLMENT') {
					//Divide main amount
					if($data["skipthefirstpayment"] == 1){
						$realFinalNumber = (int)$data["subscriptionfinalnumber"] - 1;
					} else {
						$realFinalNumber = (int)$data["subscriptionfinalnumber"];
					}
					$buildAmount["amount"] = $this->priceCurrency->round((float)($order->getBaseGrandTotal() / $realFinalNumber));
					$mainAmount = $this->_formatMainAmount($order, $buildAmount);
					$baseAmount = !strpos($mainAmount,'.')
						? $mainAmount
						: (int)str_replace('.', '', $mainAmount);
					$data['baseamount'] = (string)$baseAmount;
				}

				return $data;
			}
		}
		return null;
	}

	function _processSubscriptionInPayLoad($allVisibleItems, $amount, $currency)
	{
		foreach ($allVisibleItems as $item) {
			$options = $item->getProductOptions();
			if (isset($options["secure_trading_subscription"])) {
				foreach ($options["secure_trading_subscription"] as $key => $value) {
					if ($key == 'skipthefirstpayment' && $value == 1) {
						$data["skipthefirstpayment"] = 1;
					} else {
						$data[$key] = $value;
					}
				}
				$data["issubscription"] = '1';
				$data["credentialsonfile"] = '1';
				$data['subscriptionnumber'] = '1';
				$data["settlestatus"] = $this->scopeConfig->getValue('payment/api_secure_trading/api_settle_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				//Handle installment main amount
				if ($data["subscriptiontype"] == 'INSTALLMENT') {
					//Divide main amount
					if (isset($data["skipthefirstpayment"]) && $data["skipthefirstpayment"] == 1) {
						$realFinalNumber = (int)$data["subscriptionfinalnumber"] - 1;
					} else {
						$realFinalNumber = (int)$data["subscriptionfinalnumber"];
					}
					$buildAmount["amount"] = $this->priceCurrency->round((float)($amount / $realFinalNumber));
					$mainAmount = $this->_formatMainAmountPayLoad($currency, $buildAmount);
					$data['mainamount'] = (string)$mainAmount;
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
	function _formatTimeStamp()
	{
		$date = $this->timezone->date();
		$formattedDate  = $this->timezone->convertConfigTimeToUtc($date);
		return $formattedDate;
	}

	function _formatMainAmountPayLoad($curencyCode, array $buildSubject)
	{
		if ($curencyCode == 'JPY') {
			return strval(number_format(SubjectReader::readAmount($buildSubject), 0, '', ''));
		}
		return strval(number_format(SubjectReader::readAmount($buildSubject), 2, '.', ''));
	}

	function getVersionInformation()
	{
		$moduleVersion = $this->fullModuleList->getOne('SecureTrading_Trust');
		$stppVersion   = isset($moduleVersion['setup_version']) ? $moduleVersion['setup_version'] : "";
		/** @var \Magento\Framework\App\ProductMetadataInterface $productMetadata */
		$productMetadata = ObjectManager::getInstance()->get(\Magento\Framework\App\ProductMetadataInterface::class);
		$edition         = $productMetadata->getEdition();
		$fullVersion     = $productMetadata->getVersion();
		$str             = sprintf('Magento %s %s (SecureTrading_Trust-%s)', $edition, $fullVersion, (string)$stppVersion);
		return $str;
	}
}