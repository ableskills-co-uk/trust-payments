<?php

namespace SecureTrading\Trust\Controller\Adminhtml\Subscription;

use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use SecureTrading\Trust\Helper\Data;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Cancel
 *
 * @package SecureTrading\Trust\Controller\Adminhtml\Subscription
 */
class Cancel extends \Magento\Backend\App\Action
{
	/** @var \Magento\Framework\View\Result\PageFactory */
	protected $resultPageFactory;

	/**
	 * @var \SecureTrading\Trust\Model\SubscriptionFactory
	 */
	protected $subscriptionFactory;

	/**
	 * @var CommandPoolInterface
	 */
	protected $commandPool;

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var PaymentDataObjectFactoryInterface
	 */
	protected $paymentDataObjectFactory;

	/**
	 * @var OrderFactory
	 */
	protected $orderFactory;

	/**
	 * @var CommandPoolInterface
	 */
	protected $commandPoolAPI;

	/**
	 * Cancel constructor.
	 * @param OrderFactory $orderFactory
	 * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
	 * @param ConfigInterface $config
	 * @param CommandPoolInterface $commandPool
	 * @param \SecureTrading\Trust\Model\SubscriptionFactory $subscriptionFactory
	 * @param \Magento\Backend\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 */
	function __construct(
		OrderFactory $orderFactory,
		PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
		ConfigInterface $config,
		CommandPoolInterface $commandPool,
		\SecureTrading\Trust\Model\SubscriptionFactory $subscriptionFactory,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		$this->paymentDataObjectFactory = $paymentDataObjectFactory;
		$this->commandPool = $commandPool;
		$this->commandPoolAPI = $commandPool;
		$this->subscriptionFactory = $subscriptionFactory;
		$this->resultPageFactory = $resultPageFactory;
		$this->config = $config;
		$this->orderFactory = $orderFactory;
		parent::__construct($context);
	}

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
	 */
	function execute()
	{
		try {
			$data = $this->_request->getParams();
			if (!empty($data['subs_id'])) {
				$subscription = $this->subscriptionFactory->create()->load($data['subs_id']);
				if ($subscription->getId()) {
					$order = $this->orderFactory->create()->loadByIncrementId($subscription->getOrderId());
					$payment = $order->getPayment();
					$commandSubject['payment'] = $this->paymentDataObjectFactory->create($payment);
					$commandSubject['subscription'] = $subscription;

					if ($payment->getMethod() == Data::API_MEHTOD) {
						$this->commandPoolAPI->get('subscription_stop')->execute($commandSubject);
					} else {
						$this->commandPool->get('subscription_stop')->execute($commandSubject);
					}

					$this->messageManager->addSuccessMessage(__("A subscription has been cancelled."));
					return $this->resultRedirectFactory->create()->setPath('securetrading/subscription/index');
				}
			}
			$this->messageManager->addErrorMessage(__("You can't cancel the subscription."));
			return $this->resultRedirectFactory->create()->setPath('securetrading/subscription/index');
		} catch (\Exception $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
			return $this->resultRedirectFactory->create()->setPath('securetrading/subscription/index');
		}
	}
}