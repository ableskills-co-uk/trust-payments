<?php


namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Sales\Model\Order;

class NotificationResponse extends \SecureTrading\Trust\Controller\PaymentPage\NotificationResponse
{
    public function execute()
    {
        $this->logger->debug('--- Notification Response ---');
//        try {
//            $responseParams = $this->getRequest()->getParams();
//            if (!empty($responseParams)) {
//                $multiShippingSetId = isset($responseParams['multishippingsetid']) ? $responseParams['multishippingsetid'] : null;
//                $isMultiShipping    = isset($responseParams['ismultishipping']) ? $responseParams['ismultishipping'] : 0;
//                $isSubscription     = isset($responseParams['issubscription']) ? $responseParams['issubscription'] : 0;
//                $orderIncrementId = $this->getRequest()->getParam('orderreference', null);
//                /** @var Order $order */
//                $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
//                if (!$order->getId()) {
//                    throw new \Exception(__('Could not found order.'));
//                }
//                $this->updateShippingAddress($order->getShippingAddress(),$responseParams);
//                $this->updateBillingAddress($order->getBillingAddress(),$responseParams);
//                if ($this->isValid($responseParams)) {
//                    $this->logger->debug('--- Notification Response Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
//                    if ($this->getRequest()->getParam('errorcode', null) === "0") {
//                        //Process multishipping Orders
//                        if ($isMultiShipping == 1 && $multiShippingSetId != null) {
//                            $this->processMultiShipping($multiShippingSetId, $responseParams);
//                        } else if ($isSubscription == 1) {
//                            $this->handlerSubscriptionOrder($order,$responseParams);
//                        } else {
//                            $this->handlerNormalOrder($order,$responseParams);
//                        }
//                    } else if (isset($responseParams['settlestatus']) && $responseParams['settlestatus'] == '3') {
//                        $order->cancel();
//                        $order->save();
//                    }
//                } else {
//                    $order->cancel();
//                    $order->addCommentToStatusHistory(__('Invalid response site security.'));
//                    $order->save();
//                    $this->logger->debug('--- Notification Response Error: Invalid response site security.');
//                }
//            }
//        } catch (\Exception $exception) {
//            $this->logger->debug('--- Notification Response Error Msg: ' . $exception->getMessage() . '---');
//        }
        $this->getResponse()->setHttpResponseCode(200);
    }

    private function handlerNormalOrder(Order $order, $responseParams) {
        if (isset($responseParams['settlestatus']) && ($responseParams['settlestatus'] == '100' || $responseParams['settlestatus'] == '0')) {
            /** @var Order\Payment $payment */
            $payment = $order->getPayment();
            foreach ($responseParams as $key => $param) {
                $payment->setAdditionalInformation($key, $param);
            }
            $this->commandPool->get('authorize_capture')->execute(['order' => $order, 'info' => $responseParams]);
            $this->sendEmailAfterPayment($order);
        }
    }

    private function handlerSubscriptionOrder(Order $order, $responseParams) {
        /** @var Order\Payment $payment */
        $subscriptionFactory = $this->subscriptionFactory->create();
        $payment = $order->getPayment();
        foreach ($responseParams as $key => $param) {
            $payment->setAdditionalInformation($key, $param);
        }

        $subscriptionData = $payment->getAdditionalInformation('secure_trading_data');
        $payment->setAdditionalInformation('subscriptionunit', $subscriptionData['subscriptionunit']);
        $payment->setAdditionalInformation('subscriptionfrequency', $subscriptionData['subscriptionfrequency']);
        $payment->setAdditionalInformation('subscriptionfinalnumber', $subscriptionData['subscriptionfinalnumber']);
        $payment->setAdditionalInformation('subscriptiontype', $subscriptionData['subscriptiontype']);

        $this->processSubscription($payment, $responseParams);
        $subscriptionFactory->loadByTransactionId($payment->getAdditionalInformation('transactionreference'));
        if($subscriptionFactory->getId()){
            $payment->setAdditionalInformation('subscriptionid', $subscriptionFactory->getId());
        }
        $stData = $payment->getAdditionalInformation('secure_trading_data');
        if (!empty($responseParams['issubscription'])) {
            if ($stData['subscriptiontype'] == 'INSTALLMENT') {
                $this->commandPool->get('capture_partial')->execute(['order' => $order, 'info' => $responseParams]);
            } else {
                $this->commandPool->get($payment->getAdditionalInformation('payment_action'))->execute(['order' => $order, 'info' => $responseParams]);
            }
        }
        if ($payment->getAdditionalInformation('save_card_info') == 1)
            $this->saveCardInfotoVault($responseParams, $payment, $order);
        $this->sendEmailAfterPayment($order);
    }

    private function updateShippingAddress($shippingAddress, $responseParams) {
        if(!empty($shippingAddress)){
            $shippingAddress->setPrefix($responseParams['customerprefixname']);
            $shippingAddress->setFirstname($responseParams['customerfirstname']);
            $shippingAddress->setMiddlename($responseParams['customermiddlename']);
            $shippingAddress->setLastname($responseParams['customerlastname']);
            $shippingAddress->setStreetLine1($responseParams['customerpremise']);
            $shippingAddress->setStreetLine2($responseParams['customerstreet']);
            $shippingAddress->setCity($responseParams['customertown']);
            $shippingAddress->setRegionCode($responseParams['customercounty']);
            $shippingAddress->setPostcode($responseParams['customerpostcode']);
            $shippingAddress->setCountryId($responseParams['customercountryiso2a']);
            $shippingAddress->setEmail($responseParams['customeremail']);
            $shippingAddress->setTelephone($responseParams['customertelephone']);
            $shippingAddress->save();
        }
    }

    private function updateBillingAddress($billingAddress, $responseParams) {
        if(!empty($billingAddress)){
            $billingAddress->setPrefix($responseParams['billingprefixname']);
            $billingAddress->setFirstname($responseParams['billingfirstname']);
            $billingAddress->setMiddlename($responseParams['billingmiddlename']);
            $billingAddress->setLastname($responseParams['billinglastname']);
            $billingAddress->setStreetLine1($responseParams['billingpremise']);
            $billingAddress->setStreetLine2($responseParams['billingstreet']);
            $billingAddress->setCity($responseParams['billingtown']);
            $billingAddress->setRegionCode($responseParams['billingcounty']);
            $billingAddress->setPostcode($responseParams['billingpostcode']);
            $billingAddress->setCountryId($responseParams['billingcountryiso2a']);
            $billingAddress->setEmail($responseParams['billingemail']);
            $billingAddress->setTelephone($responseParams['billingtelephone']);
            $billingAddress->save();
        }
    }
}