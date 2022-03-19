<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class RestoreQuote
 * @package SecureTrading\Trust\Controller\ApiSecureTrading
 */
class RestoreQuote extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    protected $jsonFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var SubscriptionHelper
     */
    protected $subscriptionHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ResourceOrder
     */
    protected $resourceOrder;

    /**
     * RestoreQuote constructor.
     * @param Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Controller\Result\Json $json
     * @param SubscriptionHelper $subscriptionHelper
     * @param CheckoutSession $checkoutSession
     * @param ResourceOrder $resourceOrder
     */
    public function __construct(
        Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Controller\Result\Json $json,
        SubscriptionHelper $subscriptionHelper,
        CheckoutSession $checkoutSession,
        ResourceOrder $resourceOrder
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory  = $json;
        $this->orderFactory = $orderFactory;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->resourceOrder = $resourceOrder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $order = $this->orderFactory->create()->load($orderId);
        $this->restoreQuote($order->getIncrementId());
        $order->cancel();
        $this->resourceOrder->save($order);
        $this->jsonFactory->setData(['message' => 'sussecc']);
        return $this->jsonFactory;
    }

    /**
     * @param $order
     * @param $reponseParams
     */
    public function setAdditionalInformation($order, $reponseParams){
        $payment = $order->getPayment();
        foreach ($reponseParams as $key => $param) {
            $payment->setAdditionalInformation($key, $param);
        }
        $payment->save();
    }

    /**
     * @param $orderId
     */
    protected function restoreQuote($orderId)
    {
        $this->checkoutSession->setLastRealOrderId($orderId);
        $this->checkoutSession->restoreQuote();
        $this->checkoutSession->setLastRealOrderId(null);
    }
}
