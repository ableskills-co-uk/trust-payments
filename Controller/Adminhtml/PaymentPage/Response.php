<?php

namespace SecureTrading\Trust\Controller\Adminhtml\PaymentPage;

use Magento\Checkout\Model\Session;
use Magento\Backend\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\Logger\Logger;
use Magento\Framework\Message\ManagerInterface;

class Response extends \Magento\Backend\App\Action
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;
    /**
     * @var Logger
     */
    protected $logger;

	protected $messageManager;

    function __construct(
        Context $context,
        ConfigInterface $config,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Session $checkoutSession,
        CommandPoolInterface $commandPool,
        Logger $logger,
		ManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->config          = $config;
        $this->commandPool     = $commandPool;
        $this->logger          = $logger;
        $this->messageManager  = $messageManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    function execute()
    {
        $isUsedIframe = 0;
        try {
            $responseParams = $this->getRequest()->getParams();
            if (isset($responseParams['isusediframe'])) {
                $isUsedIframe = $responseParams['isusediframe'];
            }
            if (!empty($responseParams)) {
                $orderIncrementId = $this->getRequest()->getParam('orderreference', null);
                /** @var Order $order */
                $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

                if (empty($order->getId())) {
                    $this->messageManager->addError(__("Something went wrong. Please try again later."));
                    return $this->redirect($isUsedIframe, false, null);
                }
                /** @var Order\Payment $payment */
                $payment = $order->getPayment();
                foreach ($responseParams as $key => $param) {
                    $payment->setAdditionalInformation($key, $param);
                }
                if ($this->getRequest()->getParam('errorcode', null) === "0") {
                        return $this->redirect($isUsedIframe, true, $order->getId());

                } else {
                    $order->cancel();
                    $order->save();
                    $this->messageManager->addError(__("Order has been cancelled"));
                    return $this->redirect($isUsedIframe, false, null);
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addError(__($exception->getMessage()));
            return $this->redirect($isUsedIframe, false, null);
        }
        return $this->redirect($isUsedIframe, false, null);
    }

    /**
     * @param $isUsedIframe
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirect($isUsedIframe, $status, $orderId){
    	if(!$status){
		    return $this->resultRedirectFactory->create()->setPath('sales/order/index');
	    } else if ($isUsedIframe == 1) {
		    return $this->resultRedirectFactory->create()->setPath('securetrading/paymentpage/adminredirect?redirect_path='.urlencode('sales/order/view/order_id/'.$orderId));
	    } else {
		    $this->messageManager->addSuccessMessage(__('You created the order.'));
		    return $this->resultRedirectFactory->create()->setPath('sales/order/view',['order_id' => $orderId]);
	    }
    }
}