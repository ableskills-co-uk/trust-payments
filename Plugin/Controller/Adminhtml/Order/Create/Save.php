<?php

namespace SecureTrading\Trust\Plugin\Controller\Adminhtml\Order\Create;
use Magento\Framework\Message\ManagerInterface;
/**
 * Class Save
 * @package SecureTrading\Trust\Plugin\Controller\Adminhtml\Order\Create
 */
class Save
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    protected $_messageManager;

    public function __construct(\Magento\Framework\App\RequestInterface $request,
                                \Magento\Framework\Registry $registry,
                                ManagerInterface $messageManager)
    {
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_messageManager = $messageManager;
    }

    /**
     * @param \Magento\Sales\Controller\Adminhtml\Order\Create\Save $subject
     * @param $result
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function afterExecute(\Magento\Sales\Controller\Adminhtml\Order\Create\Save $subject, $result){
        $tempPaymentData = $this->getRequest()->getParam('payment');

        $orderId = $this->_registry->registry('order_id');
        $this->_registry->unregister('order_id');

        if($result instanceof \Magento\Framework\Controller\Result\Redirect && !$result instanceof \Magento\Framework\Controller\Result\Forward){
            if ($tempPaymentData['method'] == 'secure_trading') {
	            $this->_messageManager->getMessages(true);
	            return $result->setPath('securetrading/paymentpage/adminredirect', ['order_id' => $orderId]);
            }
	        if ($tempPaymentData['method'] == 'api_secure_trading') {
	        	$this->_messageManager->getMessages(true);
		        return $result->setPath('securetrading/apisecuretrading/adminredirect', ['order_id' => $orderId]);
	        }
            return $result;
        }
        return $result;
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }
}