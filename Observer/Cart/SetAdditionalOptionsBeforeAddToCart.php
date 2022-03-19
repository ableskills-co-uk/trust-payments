<?php

namespace SecureTrading\Trust\Observer\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class SetAdditionalOptionsBeforeAddToCart
 *
 * @package SecureTrading\Trust\Observer\Cart
 */
class SetAdditionalOptionsBeforeAddToCart implements ObserverInterface
{
	/**
	 * @var RequestInterface
	 */
	protected $_request;

	/**
	 * @var Json
	 */
	protected $serializer;

	/**
	 * @var SubscriptionHelper
	 */
	protected $helper;
	/**
	 * @var array
	 */
	protected $label = [
		"subscriptionunit"        => "Subscription Unit",
		"subscriptionfrequency"   => "Subscription Frequency",
		"subscriptionfinalnumber" => "Subscription Final Number",
		"subscriptiontype"        => "Subscription Type",
		"skipthefirstpayment"     => "Free Trial"
	];


	/**
	 * SetAdditionalOptionsBeforeAddToCart constructor.
	 * @param RequestInterface $request
	 * @param Json $json
	 * @param SubscriptionHelper $helper
	 */
	public function __construct(
		RequestInterface $request,
		Json $json,
		SubscriptionHelper $helper
	) {
		$this->_request = $request;
		$this->serializer = $json;
		$this->helper = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		if ($this->_request->getFullActionName() == 'checkout_cart_add') {
			//checking when product is adding to cart
			$data = $this->_request->getParams();
			if(isset($data['secure_trading_subscription']) && $data['secure_trading_subscription'] != 'nosubscription'){
				$product = $observer->getProduct();
				$options = $this->serializer->unserialize($data['secure_trading_subscription']);
				if(isset($options['record_id'])){
					$optionId = $additionalOptions[] = array(
						'label' => 'record_id',
						'value' => $options['record_id']
					);
					$product->addCustomOption('secure_trading_subscription_option_id', $this->serializer->serialize($optionId));
				}
				unset($options['initialize']);
				unset($options['record_id']);
				unset($options['position']);
				$additionalOptions = [];
				$transactionDetail[] = array(
					'label' => 'Transaction Details',
					'value' => $this->helper->getDescription($options['skipthefirstpayment'], $options['subscriptionfrequency'], $options['subscriptionunit'], $options['subscriptionfinalnumber'], $product->getPriceInfo()->getPrice('final_price')->getValue(), $options['subscriptiontype']));
				foreach ($options as $key => $value) {
					if ($value) {
						if($key == 'skipthefirstpayment'){
							$value == 1 ? $value = __('YES') : $value = __('NO');
						}
						$additionalOptions[] = array(
							'label' => $this->label[$key],
							'value' => $value
						);
					}
				}
				$product->addCustomOption('secure_trading_subscription', $this->serializer->serialize($additionalOptions));
				$product->addCustomOption('secure_trading_subscription_detail', $this->serializer->serialize($transactionDetail));
			}
		}
	}
}