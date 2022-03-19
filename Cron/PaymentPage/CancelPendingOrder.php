<?php

namespace SecureTrading\Trust\Cron\PaymentPage;

use SecureTrading\Trust\Helper\Logger\Logger;
use Magento\Sales\Model\Order;
use SecureTrading\Trust\Model\Ui\ConfigProvider;
use SecureTrading\Trust\Helper\Data;

/**
 * Class CancelPendingOrder
 *
 * @package SecureTrading\Trust\Cron\PaymentPage
 */
class CancelPendingOrder
{
    /**
     *
     */
    const DEFAULT_INTERVAL_CANCEL_ORDER   = '3';
    /**
     *
     */
    const TIME_INTERVAL_CANCEL_ORDER_PATH = 'payment/secure_trading/configurable_cron/time_interval_run_cron';

	/**
	 * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
	 */
	protected $paymentCollectionFactory;

	/**
	 * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
	 */
	protected $orderCollectionFactory;

	/**
	 * @var Logger
	 */
	protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * CancelPendingOrder constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \SecureTrading\Trust\Helper\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
	    \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Logger $logger
    ) {
        $this->config                   = $config;
		$this->paymentCollectionFactory = $paymentCollectionFactory;
		$this->orderCollectionFactory   = $orderCollectionFactory;
		$this->logger                   = $logger;
	}

	/**
	 * @throws \Exception
	 */
	public function execute()
	{
		//Checkout Sessions expire 3 hours after creation
        $timeIntervalConfig = $this->config->getValue(self::TIME_INTERVAL_CANCEL_ORDER_PATH);
		$date     = new \DateTime('now');
        preg_match('#(?<hour>\d{2}),(?<min>\d{2}),(?<sec>\d{2})#', $timeIntervalConfig, $timeArray);
		if ($timeIntervalConfig && ($timeArray['hour'] != '00' || $timeArray['min'] != '00')) {
            $date->sub(new \DateInterval('PT' . $timeArray['hour'] . 'H'));
            $date->sub(new \DateInterval('PT' . $timeArray['min'] . 'M'));
        } else {
            $date->sub(new \DateInterval('PT' . self::DEFAULT_INTERVAL_CANCEL_ORDER . 'H'));
        }
		$time = $date->format('Y-m-d H:i:s');
		$this->logger->debug($time);

		/** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $paymentCollection */
		$paymentCollection = $this->paymentCollectionFactory->create();
		$paymentCollection->addFieldToFilter('method', array('in' => array(ConfigProvider::CODE, ConfigProvider::API_CODE)));
		$orderIds = $paymentCollection->getColumnValues('parent_id');

		/** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
		$orderCollection = $this->orderCollectionFactory->create()
                                                        ->addFieldToFilter('entity_id', ['in' => $orderIds])
                                                        ->addFieldToFilter('created_at', ['lt' => $time])
                                                        ->addFieldToFilter('status', ['eq' => Data::ORDER_STATUS]);
		$i = 0;
		/** @var Order $order */
		foreach ($orderCollection->getItems() as $order) {
			$isComplete = $order->getpayment()->getAdditionalInformation('is_complete') === true;
			$this->logger->debug($order->getIncrementId());
			if (!$isComplete && $order->canCancel()) {
				$order->cancel();
				$order->addStatusHistoryComment(__("Auto cancel pending order after %1 hours (STPP)"));
				$order->save();
				$i++;
			}
		}
		$this->logger->debug(__("%1 orders have been canceled due to timeout.", $i));
	}
}
