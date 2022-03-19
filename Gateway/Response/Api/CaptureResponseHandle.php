<?php

namespace SecureTrading\Trust\Gateway\Response\Api;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class CaptureResponseHandle
 * @package SecureTrading\Trust\Gateway\Response\Api
 */
class CaptureResponseHandle implements HandlerInterface
{


	/**
	 * @var PaymentTokenFactoryInterface
	 */
	protected $paymentTokenFactory;
	/**
	 * @var Json
	 */
	protected $json;
	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;

	/**
	 * CaptureResponseHandle constructor.
	 * @param PaymentTokenFactoryInterface $paymentTokenFactory
	 * @param Json $json
	 * @param SubscriptionHelper $subscriptionHelper
	 */
	public function __construct(
		PaymentTokenFactoryInterface $paymentTokenFactory,
		Json $json,
		SubscriptionHelper $subscriptionHelper
	)
	{
		$this->paymentTokenFactory = $paymentTokenFactory;
		$this->json                = $json;
		$this->subscriptionHelper  = $subscriptionHelper;
	}

	/**
	 * @param array $handlingSubject
	 * @param array $response
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function handle(array $handlingSubject, array $response)
	{
//		$paymentDO = SubjectReader::readPayment($handlingSubject);
//
//		/** @var Payment $payment */
//		$payment = $paymentDO->getPayment();
//		$order   = $payment->getOrder();
//		$additionalData = $payment->getAdditionalInformation();
//
//		if (empty($payment->getAdditionalInformation('transactionreference')) && isset($response['transactionreference'])) {
//			$payment->setAdditionalInformation('orderreference', $order->getIncrementId());
//			$payment->setAdditionalInformation('paymenttypedescription', $response['paymenttypedescription']);
//			$payment->setAdditionalInformation('transactionreference', $response['transactionreference']);
//			$payment->setAdditionalInformation('maskedpan', $response['maskedpan']);
//
//			$payment->setTransactionId($response['transactionreference']);
//
//			$payment->setAdditionalInformation('settlestatus', 0);
//			//Save card credit
//			if(isset($additionalData['save_card_info_api']) && $additionalData['save_card_info_api'] && $response['requesttypedescription'] != 'TRANSACTIONUPDATE'){
//				$this->subscriptionHelper->saveCreditCard($response, $payment, $order);
//			}
//			//Create Subscription Parent
//			$items = $order->getItems();
//			foreach($items as $item){
//				$options = $item->getProductOptions();
//				if(isset($options["secure_trading_subscription"])){
//					$this->subscriptionHelper->processSubscription($payment, $response, $options);
//				}
//			}
//		}else if(empty($payment->getAdditionalInformation('secure_trading_data')) && isset($payment->getAdditionalInformation()['multishipping_data'])){
//			$payment->setAdditionalInformation('secure_trading_data', $response);
//			$payment->setAdditionalInformation('payment_action', $response['requestData']['settlestatus'] == 2 ? 'authorize' : 'authorize_capture');
//		}else{
//			$payment->setAdditionalInformation('settlestatus',0);
//			if(empty($payment->getAdditionalInformation('secure_trading_data'))){
//				$payment->setAdditionalInformation('secure_trading_data',$response);
//			}
//		}
		$paymentDO = SubjectReader::readPayment($handlingSubject);

		/** @var Payment $payment */
		$payment = $paymentDO->getPayment();

		$payment->setAdditionalInformation('settlestatus',0);
	}
}