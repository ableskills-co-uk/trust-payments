<?php

namespace SecureTrading\Trust\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class InstallmentConfigProvider
 *
 * @package SecureTrading\Trust\Model\Ui
 */
class InstallmentConfigProvider implements ConfigProviderInterface
{
	/**
	 * @var CheckoutSession
	 */
	private $checkoutSession;

	/**
	 * @var QuoteItemRepository
	 */
	private $quoteItemRepository;

	/**
	 * @var SerializerInterface`
	 */
	private $serializer;

	/**
	 * InstallmentConfigProvider constructor.
	 *
	 * @param CheckoutSession $checkoutSession
	 * @param QuoteItemRepository $quoteItemRepository
	 * @param SerializerInterface $serializer
	 */
	function __construct(CheckoutSession $checkoutSession,
								QuoteItemRepository $quoteItemRepository,
								SerializerInterface $serializer)
	{
		$this->checkoutSession     = $checkoutSession;
		$this->quoteItemRepository = $quoteItemRepository;
		$this->serializer          = $serializer;
	}

	/**
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	function getConfig()
	{
		$data    = [];
		$quoteId = $this->checkoutSession->getQuote()->getId();
		if ($quoteId) {
			$quoteItems = $this->quoteItemRepository->getList($quoteId);
			foreach ($quoteItems as $index => $quoteItem) {
				if ($quoteItem->getOptionByCode('secure_trading_subscription')) {
					$infoBuyRequest = $quoteItem->getBuyRequest();
					if (is_object($infoBuyRequest)) {
						$data['installment'] = $this->serializer->unserialize($infoBuyRequest->getSecureTradingSubscription());
						if (!empty($data['installment']['subscriptiontype'])) {
							if ($data['installment']['subscriptiontype'] == 'INSTALLMENT' || $data['installment']['subscriptiontype'] == 'RECURRING') {
								return $data;
							}
						}
					}
				}
			}
		}
		return [];
	}
}