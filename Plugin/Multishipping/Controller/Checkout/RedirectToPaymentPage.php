<?php

namespace SecureTrading\Trust\Plugin\Multishipping\Controller\Checkout;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use SecureTrading\Trust\Helper\MultiShippingHelper;

/**
 * Class RedirectToPaymentPage
 *
 * @package SecureTrading\Trust\Plugin\Multishipping\Controller\Checkout
 */
class RedirectToPaymentPage
{
	/**
	 * @var \Magento\Framework\Controller\Result\RedirectFactory
	 */
	protected $resultRedirectFactory;

	/**
	 * @var SessionManagerInterface
	 */
	protected $session;

	/**
	 * @var Multishipping
	 */
	protected $checkout;

	/**
	 * @var MultiShippingHelper
	 */
	protected $helper;

	protected $commandPool;

	public function __construct(Context $context,
								SessionManagerInterface $session,
								Multishipping $checkoutMultishipping,
								MultiShippingHelper $helper,
								CommandPoolInterface $commandPool)
	{
		$this->resultRedirectFactory = $context->getResultRedirectFactory();
		$this->session               = $session;
		$this->checkout              = $checkoutMultishipping;
		$this->helper                = $helper;
		$this->commandPool           = $commandPool;
	}

	/**
	 * @param \Magento\Multishipping\Controller\Checkout\OverviewPost $subject
	 * @param \Closure $proceed
	 * @return \Magento\Framework\Controller\Result\Redirect|mixed
	 */
	public function aroundExecute(\Magento\Multishipping\Controller\Checkout\OverviewPost $subject, \Closure $proceed)
	{
		$paymentInstance = $this->checkout->getQuote()->getPayment();
		$result          = $proceed();
		$paymentMethod   = $paymentInstance->getMethod();
		if ($paymentMethod == \SecureTrading\Trust\Model\Ui\ConfigProvider::CODE) {
			if (count($this->session->getAddressErrors()) == 0 && $this->session->getParentOrderId() != null) {
				$path = 'securetrading/paymentpage/multishipping';
				if ($this->session->getIsUsedIframe() == 1) {
					$path = 'securetrading/paymentpage/iframe';
				}
				$result = $this->resultRedirectFactory->create()
					->setPath(
						$path,
						['orderId' => $this->session->getParentOrderId(), 'multishipping' => 1]
					);
			}
		}elseif ($paymentMethod == \SecureTrading\Trust\Model\Ui\ConfigProvider::API_CODE){
			if (count($this->session->getAddressErrors()) == 0 && $this->session->getParentOrderId() != null) {
				$path = 'securetrading/apisecuretrading/multishipping';
				$result = $this->resultRedirectFactory->create()
					->setPath(
						$path,
						['orderId' => $this->session->getParentOrderId(), 'multishipping' => 1]
					);
			}
		}
		return $result;
	}
}