<?php

namespace SecureTrading\Trust\Controller\Adminhtml\PaymentPage;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class AdminRedirect
 * @package SecureTrading\Trust\Controller\Adminhtml\PaymentPage
 */
class AdminRedirect extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * AdminRedirect constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ){
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

}