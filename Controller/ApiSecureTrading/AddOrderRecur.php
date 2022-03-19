<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Helper\SubscriptionHelper;

class AddOrderRecur extends \Magento\Framework\App\Action\Action
{
	protected $jsonFactory;

	protected $orderFactory;

	protected $subscriptionHelper;

	public function __construct(
		Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Controller\Result\Json $json,
		SubscriptionHelper $subscriptionHelper
	) {
		parent::__construct($context);
		$this->jsonFactory  = $json;
		$this->orderFactory = $orderFactory;
		$this->subscriptionHelper = $subscriptionHelper;
	}

	public function execute()
	{
		$data = $this->getRequest()->getParam('reponse');
		$data = json_decode($data);
		$parentOrder = $this->orderFactory->create()->load($data['order']);
		$order = $this->subscriptionHelper->createOrder($parentOrder);
		$payment = $order->getPayment();
		$payment->setAmountAuthorized($order->getTotalDue());
		$payment->setBaseAmountAuthorized($order->getBaseTotalDue());
		$payment->capture(null);
		$order->save();
	}

	public function setAdditionalInformation($order, $reponseParams){
		$payment = $order->getPayment();
		foreach ($reponseParams as $key => $param) {
			$payment->setAdditionalInformation($key, $param);
		}
		$payment->save();
	}
}
