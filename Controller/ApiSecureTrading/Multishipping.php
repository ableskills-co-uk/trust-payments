<?php

namespace SecureTrading\Trust\Controller\ApiSecureTrading;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class MultiShipping
 * @package SecureTrading\Trust\Controller\ApiSecureTradingstoreCode
 */
class MultiShipping extends Action
{
	/**
	 * @var PageFactory
	 */
	protected $resultPageFactory;

	/**
	 * MultiShipping constructor.
	 *
	 * @param Context $context
	 * @param PageFactory $resultPageFactory
	 */
	function __construct(
		Context $context,
		PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
	 */
	function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		return $resultPage;
	}
}
