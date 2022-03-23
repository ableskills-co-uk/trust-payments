<?php

namespace SecureTrading\Trust\Controller\Subscription;

/**
 * Class Index
 *
 * @package SecureTrading\Trust\Controller\Subscription
 */
class Index extends \Magento\Framework\App\Action\Action
{
	/** @var \Magento\Framework\View\Result\PageFactory */
	protected $resultPageFactory;

	/**
	 * @var \Magento\Framework\Registry
	 */
	protected $registry;

	/**
	 * Index constructor.
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Framework\Registry $registry
	 */
	function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Registry $registry
	) {
		$this->resultPageFactory = $resultPageFactory;
		$this->registry          = $registry;
		parent::__construct($context);
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
	 */
	function execute()
	{
		$data       = $this->_request->getParams();
		$resultPage = $this->resultPageFactory->create();
		if (isset($data['order_id'])) {
			$this->registry->register('order_id', $data['order_id']);
			$resultPage->getConfig()->getTitle()->set(__('Detail Subscription'));
		} else {
			$resultPage->getConfig()->getTitle()->set(__('My Subscription'));
		}
		return $resultPage;
	}
}