<?php

namespace SecureTrading\Trust\Block\Adminhtml\PaymentPage;

use Magento\Backend\Block\Template;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use SecureTrading\Trust\Helper\Data;

/**
 * Class Redirect
 *
 * @package SecureTrading\Trust\Block\Adminhtml\PaymentPage
 */
class Redirect extends Template
{
	/**
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var \Magento\Framework\Serialize\Serializer\Json
	 */
	protected $jsonDecode;

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	protected $messageManager;

	/**
	 * Redirect constructor.
	 *
	 * @param Template\Context $context
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param \Magento\Framework\Serialize\Serializer\Json $jsonDecode
	 * @param ConfigInterface $config
	 * @param array $data
	 */
	public function __construct(
		Template\Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Serialize\Serializer\Json $jsonDecode,
		ConfigInterFace $config,
		ManagerInterface $messageManager,
		array $data = [])
	{
		$this->orderFactory = $orderFactory;
		$this->jsonDecode   = $jsonDecode;
		$this->config       = $config;
		$this->messageManager = $messageManager;
		parent::__construct($context, $data);
	}

	/**
	 * @return array
	 */
	public function getOrderData()
	{
		$id                  = $this->getRequest()->getParam('order_id');
		$order               = $this->orderFactory->create()->load(intval($id));
		$dataBuilder         = [];
		$dataBuilder['info'] = $order->getPayment()->getAdditionalInformation('secure_trading_data');
		if (!empty($dataBuilder['info'])) {
			$dataBuilder['info']['successfulurlredirect']   = $this->getUrl('securetrading/paymentpage/response');
			$dataBuilder['info']['declinedurlnotification'] = $this->getUrl('securetrading/paymentpage/response');
		}
		$dataBuilder['url'] = $order->getPayment()->getAdditionalInformation('secure_trading_endpoint');
		return $dataBuilder;
	}

	/**
	 * @param array $keys
	 * @return bool|false|string
	 */
	public function jsonDecode(array $keys = [])
	{
		return $this->jsonDecode->serialize($keys);
	}

	/**
	 * @return mixed
	 */
	public function isUsedIframe()
	{
		return $this->config->getValue(Data::USE_IFRAME);
	}

	/**
	 * @return mixed
	 */
	public function getWidth()
	{
		return $this->config->getValue(DATA::IFRAME_WIDTH);
	}

	/**
	 * @return mixed
	 */
	public function getHeight()
	{
		return $this->config->getValue(DATA::IFRAME_HEIGHT);
	}

	/**
	 * @return string
	 */
	public function gePaymentUrl()
	{
		return $this->getUrl('securetrading/paymentpage/adminraw', ['order_id' => $this->getRequest()->getParam('order_id')]);
	}

	/**
	 * @return bool
	 */
	public function isRedirectedToOrderGrid()
	{

		if (!empty($this->getRequest()->getParam('redirect_path'))) {
			$this->messageManager->addSuccessMessage(__('You created the order.'));
			return true;
		}
		return false;
	}
}