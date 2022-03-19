<?php
namespace SecureTrading\Trust\Controller\PaymentPage;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Data;
class NotificationAll extends Response
{
	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$this->logger->debug('--- All Notification Response ---');
		$skipthefirstpayment = 0;
		try {
			$responseParams = $this->getRequest()->getParams();
			if (isset($responseParams['skipthefirstpayment'])) {
				$skipthefirstpayment = $responseParams['skipthefirstpayment'];
			}
			if (!empty($responseParams) && $responseParams['issubscription']) {
				if ($skipthefirstpayment == 1 && $responseParams['accounttypedescription'] == Data::ECOM_ACC_TYPE) {
					$this->logger->debug('--- Skip The First Payment Params: ', array($responseParams));
					$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
					/** @var Order $order */
					$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

					if (empty($order->getId())) {
						$this->messageManager->addError(__("Something went wrong. Please try again later."));
					}
					if ($this->isValid($responseParams)) {
						$this->logger->debug('--- Skip The First Payment Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
						if ($this->getRequest()->getParam('errorcode', null) === "0") {
							/** @var Order\Payment $payment */
							$payment = $order->getPayment();
							foreach ($responseParams as $key => $param) {
								$payment->setAdditionalInformation($key, $param);
							}
							$this->processSubscription($payment, $responseParams);
							$stData = $payment->getAdditionalInformation('secure_trading_data');
							if (!empty($responseParams['issubscription'])) {
								if ($stData['subscriptiontype'] == 'INSTALLMENT') {
									$this->commandPool->get('capture_partial')->execute(['order' => $order, 'info' => $responseParams]);
								} else {
									$this->commandPool->get($payment->getAdditionalInformation('payment_action'))->execute(['order' => $order, 'info' => $responseParams]);
								}
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
				}else if (($skipthefirstpayment == 1 || $skipthefirstpayment == 0) && $responseParams['accounttypedescription'] == Data::RECUR_ACC_TYPE) {
					$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
					/** @var Order $order */
					$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
					if (empty($order->getId())) {
						$this->messageManager->addError(__("Something went wrong. Please try again later."));
					}
					$payment = $order->getPayment();
					$additional_information = $payment->getAdditionalInformation();
					$additional_information['nextrecurtransaction'] = $responseParams['transactionreference'];
					$payment->setAdditionalInformation($additional_information);
					$payment->save();
				}
			}
		} catch (\Exception $exception) {
			$this->logger->debug('--- Notification Response Error Msg: ' . $exception->getMessage() . '---');

			$this->messageManager->addError(__($exception->getMessage()));
		}
		$this->getResponse()->setHttpResponseCode(200);
	}

	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
	{
		return null;
	}

	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
	}
}