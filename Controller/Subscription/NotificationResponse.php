<?php

namespace SecureTrading\Trust\Controller\Subscription;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;

/**
 * Class NotificationResponse
 *
 * @package SecureTrading\Trust\Controller\Subscription
 */
class NotificationResponse extends \SecureTrading\Trust\Controller\PaymentPage\Response implements CsrfAwareActionInterface
{
	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$this->logger->debug('--- Subscription Response ---');
		try {
			$responseParams = $this->getRequest()->getParams();
			if (!empty($responseParams)) {
				$subscriptionType = isset($responseParams['subscriptiontype']) ? $responseParams['subscriptiontype'] : '';
				$this->logger->debug('--- Subscription Response Params: ', array($responseParams));
				$orderIncrementId = $this->getRequest()->getParam('orderreference', null);
				$parentOrder      = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
				if (empty($parentOrder->getId())) {
					throw new \Magento\Framework\Exception\LocalizedException(__("The order no longer exists."));
				}

				if ($this->isValid($responseParams)) {
					switch ($subscriptionType) {
						case 'RECURRING':
							$order = $this->subscriptionHelper->createOrder($parentOrder);
							$order->save();
							$this->subscriptionHelper->addSubscriptionItem($order, $responseParams);
							$this->commandPool->get('authorize_capture')->execute(['order' => $order, 'info' => $responseParams]);
							$this->setCommentParentOrder($parentOrder, $responseParams);
							$this->setAdditionalInformation($order, $responseParams);
							break;
						case 'INSTALLMENT':
							$this->subscriptionHelper->addSubscriptionItem($parentOrder, $responseParams);
							$this->commandPool->get('capture_partial')->execute(['order' => $parentOrder, 'info' => $responseParams]);
							$this->setCommentParentOrder($parentOrder, $responseParams);
							break;
						default:
							break;
					}

				} else {
					$this->logger->debug('--- Notification Response Error: Invalid response site security.');
				}
			}
		} catch (\Exception $exception) {
			$this->logger->debug('--- Notification Response Error Msg: ' . $exception->getMessage() . '---');

			$this->messageManager->addError(__($exception->getMessage()));
		}
		$this->getResponse()->setHttpResponseCode(200);
	}

	/**
	 * @param RequestInterface $request
	 * @return InvalidRequestException|null
	 */
	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
	{
		return null;
	}

	/**
	 * @param RequestInterface $request
	 * @return bool|null
	 */
	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
	}

	public function setCommentParentOrder($parentOrder, $responseParams){
		$grandTotal = $parentOrder->getBaseGrandTotal();
		$parentOrder->addStatusHistoryComment(__('Captured amount of £%1 online. Transaction ID: "%2"', $grandTotal, $responseParams['transactionreference']), true);
		$parentOrder->save();
	}

	public function setAdditionalInformation($order, $reponseParams){
		$payment = $order->getPayment();
		foreach ($reponseParams as $key => $param) {
			$payment->setAdditionalInformation($key, $param);
		}
		$payment->save();
	}
}