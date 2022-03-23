<?php

namespace SecureTrading\Trust\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use SecureTrading\Trust\Model\MultiShippingFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Helper\MultiShippingHelper;

/**
 * Class UpdateAmountResponseHandle
 * @package SecureTrading\Trust\Gateway\Response
 */
class UpdateAmountResponseHandle implements HandlerInterface
{
	/**
	 * @var MultiShippingFactory
	 */
	protected $multiShippingFactory;
	/**
	 * @var SerializerInterface
	 */
	protected $serialize;
	/**
	 * @var CollectionFactory
	 */
	protected $collectionFactory;
	/**
	 * @var Logger
	 */
	protected $logger;

	protected $multiShippingHelper;

	/**
	 * UpdateAmountResponseHandle constructor.
	 * @param MultiShippingFactory $multiShippingFactory
	 * @param SerializerInterface $serializer
	 * @param CollectionFactory $collectionFactory
	 * @param Logger $logger
	 */
	function __construct(
		MultiShippingFactory $multiShippingFactory,
		SerializerInterface $serializer,
		CollectionFactory $collectionFactory,
		Logger $logger,
		MultiShippingHelper $multiShippingHelper
	)
	{
		$this->multiShippingFactory = $multiShippingFactory;
		$this->serialize = $serializer;
		$this->collectionFactory = $collectionFactory;
		$this->logger = $logger;
		$this->multiShippingHelper = $multiShippingHelper;
	}

	/**
	 * @param array $handlingSubject
	 * @param array $response
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	function handle(array $handlingSubject, array $response){
		$payment = $handlingSubject['payment']->getPayment();
		if($payment['method'] == 'api_secure_trading' && isset($payment->getAdditionalInformation()['multishipping_data'])){
			$mainAmount = $payment->getAdditionalInformation()['multishipping_data']['mainamount'];
			$amountBaseGrandTotal = (int)$payment->getCreditmemo()->getBaseGrandTotal();
			$amountUpdate = number_format(($mainAmount - $amountBaseGrandTotal),2,'','');
			$this->setAmountAfterUpdate($payment, $amountUpdate);
		}
		if ($payment['method'] == 'secure_trading' && isset($payment->getAdditionalInformation()['multishipping_data'])){
			$mainAmount = $payment->getAdditionalInformation()['multishipping_data']['mainamount'];
			$amountBaseGrandTotal = $payment->getCreditmemo()->getBaseGrandTotal();
			$amountUpdate = number_format(($mainAmount - $amountBaseGrandTotal),2,'','');
			$this->setAmountAfterUpdate($payment, $amountUpdate);
		}
	}

	/**
	 * @param $payment
	 * @param $amountUpdate
	 * @throws LocalizedException
	 */
	function setAmountAfterUpdate($payment, $amountUpdate)
	{
		if (($setId = $payment->getAdditionalInformation('multishipping_set_id')) && $amountUpdate > 0)
		{
			$multiShipping = $this->multiShippingFactory->create()->load($setId);

			if ($multiShipping->getSetId()) {
				$this->logger->debug('--- LIST ORDER IDS: ' . $multiShipping->getListOrders());
				$listId = $this->serialize->unserialize($multiShipping->getListOrders());
				$collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => array_keys($listId)]);
				foreach ($collection as $item){
					if ($item->getId() && ($item->getState() != Order::STATE_COMPLETE || $item->getState() != Order::STATE_CLOSED)){
						$payment = $item->getPayment();
						if($payment['method'] == 'api_secure_trading'){
							$multishippingData = $payment->getAdditionalInformation('multishipping_data');
							$multishippingData['mainamount'] = $this->multiShippingHelper->formatMainAmount($multishippingData['currencyiso3a'],(int)$amountUpdate/100);
							$payment->setAdditionalInformation('multishipping_data',$multishippingData);
						}elseif ($payment['method'] == 'secure_trading'){
							$multishippingData = $payment->getAdditionalInformation('multishipping_data');
							$multishippingData['mainamount'] = $amountUpdate;
							$payment->setAdditionalInformation('multishipping_data',$multishippingData);
						}
						$payment->save();
						$this->logger->debug('--- Order increment id: ' . $item->getId() . ' has been refunded');
					} else {
						throw new LocalizedException(__('Can\'t refund the related payment.'));
					}
				}
			}
		}
	}
}