<?php

namespace SecureTrading\Trust\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;

class TokenUiComponentProviderApi implements TokenUiComponentProviderInterface
{
	/**
	 * @var TokenUiComponentInterfaceFactory
	 */
	private $componentFactory;

	/**
	 * @var UrlInterface
	 */
	private $urlBuilder;

	/**
	 * TokenUiComponentProvider constructor.
	 * @param TokenUiComponentInterfaceFactory $componentFactory
	 * @param UrlInterface $urlBuilder
	 */
	function __construct(
		TokenUiComponentInterfaceFactory $componentFactory,
		UrlInterface $urlBuilder
	) {
		$this->componentFactory = $componentFactory;
		$this->urlBuilder       = $urlBuilder;
	}

	/**
	 * @param PaymentTokenInterface $paymentToken
	 * @return TokenUiComponentInterface
	 */
	function getComponentForToken(PaymentTokenInterface $paymentToken)
	{
		$jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
		$component   = $this->componentFactory->create(
			[
				'config' => [
					'code'                                                   => ConfigProvider::API_CODE,
					TokenUiComponentProviderInterface::COMPONENT_DETAILS     => $jsonDetails,
					TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
				],
				'name'   => 'SecureTrading_Trust/js/view/payment/method-renderer/api-secure-trading-vault'
			]
		);

		return $component;
	}
}
