<?php
namespace SecureTrading\Trust\Controller\PaymentPage;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Data;
/**
 * Class NotificationResponse
 *
 * @package SecureTrading\Trust\Controller\PaymentPage
 */
class VaultNotificationResponse extends Response
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->debug('--- Vault Notification Response ---');
        try {
            $responseParams = $this->getRequest()->getParams();
            if (!empty($responseParams) && $this->vaultInvalid($responseParams)) {
				$isSubscription = isset($responseParams['issubscription']) ? $responseParams['issubscription'] : 0;
                $jwtResponse = $this->decodeJWT($responseParams);
                $orderIncrementId = $responseParams['orderreference'];
                /** @var Order $order */
                $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

                if (empty($order->getId())) {
                    $this->messageManager->addError(__("Something went wrong. Please try again later."));
                }

                $this->logger->debug('--- Vault Notification Response Params: ', array($responseParams));
                /** @var Order\Payment $payment */
                $payment = $order->getPayment();
                foreach ($responseParams as $key => $param) {
                    $payment->setAdditionalInformation($key, $param);
                }
                $this->logger->debug('--- Vault Notification Response Error Code: ' . $this->getRequest()->getParam('errorcode', null) . '---');
                if ($this->getRequest()->getParam('errorcode', null) === "0") {
					if ($isSubscription == 1) {
						$this->processSubscription($payment, $responseParams);
						$stData = $payment->getAdditionalInformation('secure_trading_data');
						if (!empty($responseParams['issubscription'])) {
							if ($stData['subscriptiontype'] == 'INSTALLMENT') {
								$this->commandPool->get('capture_partial')->execute(['order' => $order, 'info' => $responseParams]);
							} else {
								$this->commandPool->get($payment->getAdditionalInformation('payment_action'))->execute(['order' => $order, 'info' => $responseParams]);
							}
						}
					} else {
						if ($payment->getAdditionalInformation('payment_action') == 'authorize') {
							$this->commandPool->get('authorize')->execute(['order' => $order, 'info' => $responseParams]);
						} elseif ($payment->getAdditionalInformation('payment_action') == 'authorize_capture') {
							$this->commandPool->get('authorize_capture')->execute(['order' => $order, 'info' => $responseParams]);
						}
					}
					$this->sendEmailAfterPayment($order);
                    return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
                } else {
                    $order->cancel();
                    $order->save();
                    $this->logger->addDebug('--- Order canceled ---');
                }
            }
        } catch (\Exception $exception) {
            $this->logger->debug('--- Vault Notification Response Error Msg: ' . $exception->getMessage() . '---');
            $this->messageManager->addError(__($exception->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
        }

        $this->logger->addDebug('--- Vault Notification Response Error Msg: '. $responseParams['errormessage'] . '---');
        $this->messageManager->addError(__('Something went wrong'));
        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
    }

    public function decodeJWT($jwtResponse)
    {
        $responseData = $this->jwt->decode($jwtResponse['jwt'],$this->config->getValue(Data::JWT_SECRET_KEY),['HS256']);
        return $responseData;
    }

    public function vaultInvalid($response)
    {
        if (!isset($response['errorcode']) || $response['errorcode'] != 0)
            return false;

        return true;

    }
}