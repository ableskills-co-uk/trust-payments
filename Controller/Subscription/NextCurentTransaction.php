<?php
namespace SecureTrading\Trust\Controller\Subscription;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Controller\PaymentPage\Response;
class NextCurentTransaction extends Response
{
	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$this->logger->debug('--- Next Transaction Response ---');
		$skipthefirstpayment = 0;
		try {
			$responseParams = $this->getRequest()->getParams();
			$this->logger->debug('--- Next Transaction Response Params: ', array($responseParams));
			if (isset($responseParams['skipthefirstpayment'])) {
				$skipthefirstpayment = $responseParams['skipthefirstpayment'];
			}
			$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
			$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
			$payment = $order->getPayment();
			if (!empty($order->getId()) && $payment->getMethod() === 'api_secure_trading' && !$payment->getAdditionalInformation('nextrecurtransaction')) {
					$this->logger->debug('--- Skip The First Payment Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
					if (($skipthefirstpayment == 1 || $skipthefirstpayment == 0) && $responseParams['accounttypedescription'] == Data::RECUR_ACC_TYPE) {
						$additional_information = $payment->getAdditionalInformation();
						$additional_information['nextrecurtransaction'] = $responseParams['transactionreference'];
						$payment->setAdditionalInformation($additional_information);
						$payment->save();
					}
			}else {
				$this->logger->debug('--- Notification Response Error Msg: Order or Payment fails ---');
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