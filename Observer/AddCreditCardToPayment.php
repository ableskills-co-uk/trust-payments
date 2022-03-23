<?php

namespace SecureTrading\Trust\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;

class AddCreditCardToPayment extends AbstractDataAssignObserver
{
	/**
	 * @param Observer $observer
	 * @return void
	 */
	function execute(\Magento\Framework\Event\Observer $observer)
	{
		try{
			$data = $this->readDataArgument($observer);

			$additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

			if (!is_array($additionalData) || count($additionalData) == 0) {
				return;
			}

			$paymentModel = $this->readPaymentModelArgument($observer);

			$payment = $observer->getPaymentModel();

//			$payment->setAdditionalInformation('api_secure_trading_data', $additionalData['api_secure_trading_data']);
//
//			isset($additionalData['paymentMultishipping']) ? $payment->setAdditionalInformation('payment_action',$additionalData['paymentMultishipping']) : $payment->setAdditionalInformation('payment_action',$additionalData['payment_action']);

			if (!$payment instanceof InfoInterface) {
				$payment = $paymentModel->getInfoInstance();
			}

			if (!$payment instanceof InfoInterface) {
				throw new LocalizedException(__('Payment model does not provided.'));
			}

			if(isset($additionalData['save_card_info_api'])){
				$payment->setAdditionalInformation('save_card_info_api',$additionalData['save_card_info_api']);

			}
		}catch (\Exception $exception){
			throw new \Magento\Framework\Exception\LocalizedException(__('Some thing went wrong'));
		}
	}
}
