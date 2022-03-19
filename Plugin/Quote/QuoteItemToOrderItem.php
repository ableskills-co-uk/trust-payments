<?php

namespace SecureTrading\Trust\Plugin\Quote;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class QuoteItemToOrderItem
 *
 * @package SecureTrading\Trust\Plugin\Quote
 */
class QuoteItemToOrderItem
{
	/**
	 * @var Json
	 */
	protected $serializer;

	/**
	 * QuoteItemToOrderItem constructor.
	 *
	 * @param Json $json
	 */
	public function __construct(Json $json)
	{
		$this->serializer = $json;
	}

	/**
	 * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
	 * @param $result
	 * @param $quoteItem
	 * @param $data
	 * @return mixed
	 */
	public function afterConvert(\Magento\Quote\Model\Quote\Item\ToOrderItem $subject, $result, $quoteItem, $data = [])
	{
		// get order item
		$orderItem = $result;
		if ($additionalOptionsQuote = $quoteItem->getOptionByCode('secure_trading_subscription')) {
			if($subscriptionOptionsToOrder = $quoteItem->getBuyRequest()->getData('secure_trading_subscription')) {
				if($subscriptionOptionsToOrder !='nosubscription') {
					$subscriptionOptionsToOrder = $this->serializer->unserialize($subscriptionOptionsToOrder);

					unset($subscriptionOptionsToOrder['initialize']);
					unset($subscriptionOptionsToOrder['record_id']);
					unset($subscriptionOptionsToOrder['position']);

					if ($additionalOptionsOrder = $orderItem->getProductOptionByCode('additional_options')) {
						$additionalOptions = array_merge($additionalOptionsQuote, $subscriptionOptionsToOrder);
					} else {
						$additionalOptions = $subscriptionOptionsToOrder;
					}
					if (count($additionalOptions) > 0) {
						$options                                = $orderItem->getProductOptions();
						$options['secure_trading_subscription'] = $additionalOptions;
						$orderItem->setProductOptions($options);
					}
				}
			}
		}
		return $orderItem;
	}
}