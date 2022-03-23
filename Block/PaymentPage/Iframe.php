<?php

namespace SecureTrading\Trust\Block\PaymentPage;

use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class Iframe
 *
 * @package SecureTrading\Trust\Block\PaymentPage
 */
class Iframe extends Template
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
	 * Iframe constructor.
	 *
	 * @param Template\Context $context
	 * @param ConfigInterface $config
	 * @param array $data
	 */
	function __construct(
		Template\Context $context,
		ConfigInterface $config,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->orderFactory = $orderFactory;
		$this->config       = $config;
	}

	/**
	 * @return string
	 */
	function gePaymentUrl()
	{
		return $this->getUrl('securetrading/paymentpage/raw',
			['orderId'       => $this->getRequest()->getParam('orderId'),
			 'multishipping' => $this->getRequest()->getParam('multishipping')]);
	}

	/**
	 * @return mixed
	 */
	function getWidth()
	{
		return $this->config->getValue(DATA::IFRAME_WIDTH);
	}

	/**
	 * @return mixed
	 */
	function getHeight()
	{
		return $this->config->getValue(DATA::IFRAME_HEIGHT);
	}
}