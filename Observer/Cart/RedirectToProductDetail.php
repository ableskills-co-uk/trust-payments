<?php

namespace SecureTrading\Trust\Observer\Cart;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class RedirectToProductDetail
 *
 * @package SecureTrading\Trust\Observer\Cart
 */
class RedirectToProductDetail implements ObserverInterface
{
	/**
	 * @var Cart
	 */
	protected $cart;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * RedirectToProductDetail constructor.
	 *
	 * @param Cart $cart
	 * @param SerializerInterface $serializer
	 */
	function __construct(Cart $cart, SerializerInterface $serializer)
	{
		$this->cart = $cart;
		$this->serializer = $serializer;
	}

	/**
	 * @param Observer $observer
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	function execute(Observer $observer)
	{
		$product = $observer->getProduct();
		$request = $observer->getInfo();
		if ($product->getStppEnableSubs() && $product->getStppRequireSubs() && empty($request["secure_trading_subscription"])) {
			throw new \Magento\Framework\Exception\LocalizedException(__("You need to choose an option for subscription."));
		}

		$quoteItems = $this->cart->getQuote()->getAllVisibleItems();
		$buyInfo = $request = $observer->getInfo();
		foreach ($quoteItems as $quoteItem) {
			if ($quoteItem->getOptionByCode('secure_trading_subscription') && $quoteItem->getProductId() != $product->getId()) {
				throw new \Magento\Framework\Exception\LocalizedException(__("You can't add non-subscription items to the cart if there are subscription items in the cart"));
			}
			if(isset($buyInfo['secure_trading_subscription']) && $product->getStppEnableSubs()){
					if($quoteItem->getProductId() != $product->getId() && $buyInfo['secure_trading_subscription'] != 'nosubscription'){
						throw new \Magento\Framework\Exception\LocalizedException(__("You can't add subscription items to the cart if there are non-subscription items in the cart"));
					} else {
						$msg = __("Please remove the product in the cart before changing option.");
						if ($buyInfo['secure_trading_subscription'] != 'nosubscription') {
							$optionId = $quoteItem->getOptionByCode('secure_trading_subscription_option_id')->getValue();
							if (isset($optionId)) {
								$oldOptionId = $this->serializer->unserialize($optionId);
								$newOptionId = $this->serializer->unserialize($buyInfo['secure_trading_subscription']);
								if ($oldOptionId['value'] != $newOptionId['record_id']) {
									throw new \Magento\Framework\Exception\LocalizedException($msg);
								}
							} elseif ($quoteItem->getOptionByCode('secure_trading_subscription')){
								throw new \Magento\Framework\Exception\LocalizedException($msg);
							}
						} else {
							if ($quoteItem->getOptionByCode('secure_trading_subscription')) {
								throw new \Magento\Framework\Exception\LocalizedException($msg);
							}
						}
					}
			}
		}
	}
}