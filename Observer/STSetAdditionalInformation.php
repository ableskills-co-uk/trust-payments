<?php

namespace SecureTrading\Trust\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;

/**
 * Class STSetAdditionalInformation
 *
 * @package  SecureTrading\Trust\Observer
 */
class STSetAdditionalInformation extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        if (isset($additionalData['save_card_info']) || isset($additionalData['public_hash'])) {
            $paymentModel = $this->readPaymentModelArgument($observer);

            $payment = $observer->getPaymentModel();
            if (!$payment instanceof InfoInterface) {
                $payment = $paymentModel->getInfoInstance();
            }
            $payment->setAdditionalInformation($additionalData);
        }
    }
}
