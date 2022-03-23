<?php

namespace SecureTrading\Trust\Controller\PaymentPage;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

/**
 * Class Start
 *
 * @package SecureTrading\Trust\Controller\Order
 */
class Start extends \Magento\Framework\App\Action\Action
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
	 * Start constructor.
	 *
	 * @param Context $context
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param \Magento\Framework\Controller\Result\Json $json
	 */
	function __construct(
		Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Controller\Result\Json $json
	) {
		parent::__construct($context);
		$this->jsonFactory  = $json;
		$this->orderFactory = $orderFactory;
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
	 */
	function execute()
	{
		$id = $this->getRequest()->getParam('order_id', 0);
		/** @var Order $order */
		$order               = $this->orderFactory->create()->load(intval($id));
		$dataBuilder         = [];
		$dataBuilder['info'] = $order->getPayment()->getAdditionalInformation('secure_trading_data');
		$dataBuilder['url']  = $order->getPayment()->getAdditionalInformation('secure_trading_endpoint');
		$this->jsonFactory->setData($dataBuilder);

		return $this->jsonFactory;
	}
}
