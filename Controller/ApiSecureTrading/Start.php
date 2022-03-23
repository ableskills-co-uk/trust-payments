<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

class Start extends \Magento\Framework\App\Action\Action
{
	protected $jsonFactory;

	protected $orderFactory;

	function __construct(
		Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Controller\Result\Json $json
	) {
		parent::__construct($context);
		$this->jsonFactory  = $json;
		$this->orderFactory = $orderFactory;
	}

	function execute()
	{
		$data = $this->getRequest()->getParams();
		/** @var Order $order */
		$order               = $this->orderFactory->create()->load(intval($data['order_id']));
		$dataBuilder         = [];
		$dataBuilder['info'] = $data['additional_information'];

		$this->jsonFactory->setData($dataBuilder);

		return $this->jsonFactory;
	}
}
