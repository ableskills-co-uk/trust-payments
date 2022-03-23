<?php

namespace SecureTrading\Trust\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Detail
 *
 * @package SecureTrading\Trust\Controller\Adminhtml\Subscription
 */
class Detail extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

	/**
	 * @var Registry
	 */
	protected $coreRegistry;

	/**
	 * Detail constructor.
	 *
	 * @param Registry $registry
	 * @param Context $context
	 * @param PageFactory $resultPageFactory
	 */
	function __construct(
        Registry $registry,
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry      = $registry;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $poid       = $this->getRequest()->getParam('poid');

        if (!isset($poid)) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*');
        }
        $this->coreRegistry->register('poid', $poid);

        $resultPage->setActiveMenu('SecureTrading_Trust::subscription_detail');
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription Detail'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('SecureTrading_Trust::subscription_detail');
    }
}
