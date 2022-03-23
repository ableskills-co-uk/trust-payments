<?php
namespace SecureTrading\Trust\Controller\PaymentPage;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
/**
 * Class NotificationDecline
 *
 * @package SecureTrading\Trust\Controller\PaymentPage
 */
class NotificationDecline extends Response
{
	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	function execute()
	{
		$this->logger->debug('--- Notification Decline ---');
		$isMultiShipping    = 0;
		$multiShippingSetId = null;
		try {
			$responseParams = $this->getRequest()->getParams();
			if (isset($responseParams['ismultishipping'])) {
				$isMultiShipping = $responseParams['ismultishipping'];
			}
			if (!empty($responseParams['multishippingsetid'])) {
				$multiShippingSetId = $responseParams['multishippingsetid'];
			}
			if (!empty($responseParams)) {
				$this->logger->debug('--- Notification Decline Params: ', array($responseParams));
				$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
				/** @var Order $order */
				$order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

				if (empty($order->getId())) {
					$this->messageManager->addError(__("Something went wrong. Please try again later."));
				}

				$errorCode = $this->getRequest()->getParam('errorcode', null);
				$this->logger->debug('--- Notification Decline Error Code: ' . $errorCode . '---');
				if($isMultiShipping == 1 && $multiShippingSetId != null){
					$this->declineMultiShipping($multiShippingSetId, $responseParams);
				}else{
					$order->addCommentToStatusHistory(__('Transaction has been declined. Request reference: %1', $responseParams['requestreference']));
					$order->save();
				}
			}
		} catch (\Exception $exception) {
			$this->logger->debug('--- Notification Decline Error Msg: ' . $exception->getMessage() . '---');

			$this->messageManager->addError(__($exception->getMessage()));
		}
		$this->getResponse()->setHttpResponseCode(200);
	}
}