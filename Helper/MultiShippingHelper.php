<?php

namespace SecureTrading\Trust\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use SecureTrading\Trust\Model\MultiShippingFactory;

/**
 * Class MultiShippingHelper
 *
 * @package SecureTrading\Trust\Helper
 */
class MultiShippingHelper extends AbstractHelper
{
	/**
	 * @var OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var Json
	 */
	protected $serializer;

	/**
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;

	/**
	 * @var SessionManagerInterface
	 */
	protected $session;

	/**
	 * MultiShippingHelper constructor.
	 *
	 * @param Context $context
	 * @param OrderFactory $orderFactory
	 * @param ConfigInterface $config
	 * @param SerializerInterface $serializer
	 * @param SessionManagerInterface $session
	 * @param MultiShippingFactory $multiShippingFactory
	 */
	public function __construct(Context $context,
								OrderFactory $orderFactory,
								ConfigInterface $config,
								SerializerInterface $serializer,
								SessionManagerInterface $session,
								MultiShippingFactory $multiShippingFactory)
	{
		$this->orderFactory         = $orderFactory;
		$this->config               = $config;
		$this->serializer           = $serializer;
		$this->multiShippingFactory = $multiShippingFactory;
		$this->session              = $session;
		parent::__construct($context);
	}

	/**
	 * @param $currency
	 * @param $totalAmount
	 * @return string
	 */
	public function formatMainAmount($currency, $totalAmount)
	{
		if ($currency == 'JPY') {
			return strval(number_format($totalAmount, 0, '', ''));
		}
		return strval(number_format($totalAmount, 2, '.', ''));
	}

	/**
	 * @param $order
	 * @return array
	 */
	public function getPaymentAdditionalInformation($order)
	{
		if ($order->getId()) {
			$data = $order->getPayment()->getAdditionalInformation('secure_trading_data');
			return $data;
		} else {
			return [];
		}
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function reHashData($data)
	{
		$sitesecurity         = $this->config->getSiteSecurity($data);
		$data['sitesecurity'] = $sitesecurity;

		return $data;
	}

	/**
	 * @return \SecureTrading\Trust\Model\MultiShipping
	 * @throws \Exception
	 */
	public function saveMultiShippingData()
	{
		$orderIds      = $this->session->getOrderIds();
		$multiShipping = $this->multiShippingFactory->create();
		if ($orderIds) {
			if (is_array($orderIds)) {
				$orderIds = $this->serializer->serialize($orderIds);
			}
			$multiShipping->setListOrders($orderIds);
			$multiShipping->save();
		}
		return $multiShipping;
	}

	/**
	 * @param $orders
	 * @param $data
	 * @param $setId
	 */
	public function saveParentOrderData($orders, $data, $setId)
	{
		$isComplete = false;
		foreach ($orders as $order) {
			if ($isComplete == false) {
				$parentOrderId = $order->getId();
				$isComplete == true;
			}
			$payment = $order->getPayment();

			$payment->setAdditionalInformation('multishipping_data', $data);
			$payment->setAdditionalInformation('multishipping_set_id', $setId);

			$payment->save();
		}
		$this->session->setParentOrderId($parentOrderId);
		$this->session->setDataApiSecureTrading($data);
		if (isset($data['isusediframe'])) {
			$this->session->setIsUsedIframe($data['isusediframe']);
		}
	}
}

