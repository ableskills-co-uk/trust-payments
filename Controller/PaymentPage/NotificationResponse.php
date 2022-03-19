<?php
namespace SecureTrading\Trust\Controller\PaymentPage;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
/**
 * Class NotificationResponse
 *
 * @package SecureTrading\Trust\Controller\PaymentPage
 */
class NotificationResponse extends Response
{
	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$this->logger->debug('--- Notification Response ---');
		try {
			$responseParams = $this->getRequest()->getParams();
			if (!empty($responseParams)) {
				$multiShippingSetId = isset($responseParams['multishippingsetid']) ? $responseParams['multishippingsetid'] : null;
				$isMultiShipping    = isset($responseParams['ismultishipping']) ? $responseParams['ismultishipping'] : 0;
				$isSubscription     = isset($responseParams['issubscription']) ? $responseParams['issubscription'] : 0;
				$this->logger->debug('--- Notification Response Params: ', array($responseParams));
				$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
				/** @var Order $order */
				$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
				if ($order->getPayment()->getAdditionalInformation('transactionreference')) {
                    $this->getResponse()->setHttpResponseCode(200);
                    return;
                }

				//Handel address if customer change in payment page
				$orderAddress = $order->getShippingAddress();
				if(!empty($orderAddress)){
                    $orderAddress->setPrefix($responseParams['customerprefixname']);
                    $orderAddress->setFirstname($responseParams['customerfirstname']);
                    $orderAddress->setMiddlename($responseParams['customermiddlename']);
                    $orderAddress->setLastname($responseParams['customerlastname']);
                    $orderAddress->setStreetLine1($responseParams['customerpremise']);
                    $orderAddress->setStreetLine2($responseParams['customerstreet']);
                    $orderAddress->setCity($responseParams['customertown']);
                    $orderAddress->setRegionCode($responseParams['customercounty']);
                    $orderAddress->setPostcode($responseParams['customerpostcode']);
                    $orderAddress->setCountryId($responseParams['customercountryiso2a']);
                    $orderAddress->setEmail($responseParams['customeremail']);
                    $orderAddress->setTelephone($responseParams['customertelephone']);
                    $orderAddress->save();
                }

				//Handel billing if customer change in payment page
				$orderBillingAddress = $order->getBillingAddress();
				if(!empty($orderBillingAddress)){
                    $orderBillingAddress->setPrefix($responseParams['billingprefixname']);
                    $orderBillingAddress->setFirstname($responseParams['billingfirstname']);
                    $orderBillingAddress->setMiddlename($responseParams['billingmiddlename']);
                    $orderBillingAddress->setLastname($responseParams['billinglastname']);
                    $orderBillingAddress->setStreetLine1($responseParams['billingpremise']);
                    $orderBillingAddress->setStreetLine2($responseParams['billingstreet']);
                    $orderBillingAddress->setCity($responseParams['billingtown']);
                    $orderBillingAddress->setRegionCode($responseParams['billingcounty']);
                    $orderBillingAddress->setPostcode($responseParams['billingpostcode']);
                    $orderBillingAddress->setCountryId($responseParams['billingcountryiso2a']);
                    $orderBillingAddress->setEmail($responseParams['billingemail']);
                    $orderBillingAddress->setTelephone($responseParams['billingtelephone']);
                    $orderBillingAddress->save();
                }

				$paymentMethod = $order->getPayment()->getMethod();
				if($paymentMethod !== 'api_secure_trading') {
					if (empty($order->getId())) {
						$this->messageManager->addError(__("Something went wrong. Please try again later."));
					}
					if ($this->isValid($responseParams)) {
						$this->logger->debug('--- Notification Response Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
						if ($this->getRequest()->getParam('errorcode', null) === "0") {
							//Process multishipping Orders
							if ($isMultiShipping == 1 && $multiShippingSetId != null) {
								$this->processMultiShipping($multiShippingSetId, $responseParams);
							} else if ($isSubscription == 1) {
								//Process Subscription Orders
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
							} else if ( $isSubscription!=1 && $isMultiShipping !=1 && ($responseParams['settlestatus'] == '100' || $responseParams['settlestatus'] == '0')) {
								//Process Normal Orders
								/** @var Order\Payment $payment */
								$payment = $order->getPayment();
								foreach ($responseParams as $key => $param) {
									$payment->setAdditionalInformation($key, $param);
								}
								if ($payment->getAdditionalInformation('payment_action') == 'authorize') {
									$this->commandPool->get('authorize')->execute(['order' => $order, 'info' => $responseParams]);
								} elseif ($payment->getAdditionalInformation('payment_action') == 'authorize_capture') {
									$this->commandPool->get('authorize_capture')->execute(['order' => $order, 'info' => $responseParams]);
								}

								if ($payment->getAdditionalInformation('save_card_info') == 1)
									$this->saveCardInfotoVault($responseParams, $payment, $order);
								$this->sendEmailAfterPayment($order);
							}

						} else {
							$order->cancel();
							$order->save();
						}
					} else {
						$order->cancel();
						$order->addCommentToStatusHistory(__('Invalid response site security.'));
						$order->save();
						$this->logger->debug('--- Notification Response Error: Invalid response site security.');
					}
				}
			}
		} catch (\Exception $exception) {
			$this->logger->debug('--- Notification Response Error Msg: ' . $exception->getMessage() . '---');

			$this->messageManager->addError(__($exception->getMessage()));
		}
		$this->getResponse()->setHttpResponseCode(200);
	}
}
