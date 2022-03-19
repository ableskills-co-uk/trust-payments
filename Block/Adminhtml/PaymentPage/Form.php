<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SecureTrading\Trust\Block\Adminhtml\PaymentPage;

use Magento\Backend\Model\Session\Quote;
use SecureTrading\Trust\Gateway\Config\Config as GatewayConfig;
use SecureTrading\Trust\Model\Adminhtml\Source\CcType;
use SecureTrading\Trust\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Form
 */
class Form extends Cc
{
	protected $_template = 'SecureTrading_Trust::checkout/formcc.phtml';
	/**
	 * @var Quote
	 */
	protected $sessionQuote;

	/**
	 * @var Config
	 */
	protected $gatewayConfig;

	/**
	 * @var CcType
	 */
	protected $ccType;

	/**
	 * @var Data
	 */
	private $paymentDataHelper;

	protected $scopeConfig;
	/**
	 * @param Context $context
	 * @param Config $paymentConfig
	 * @param Quote $sessionQuote
	 * @param GatewayConfig $gatewayConfig
	 * @param CcType $ccType
	 * @param Data $paymentDataHelper
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		Config $paymentConfig,
		Quote $sessionQuote,
		GatewayConfig $gatewayConfig,
		CcType $ccType,
		Data $paymentDataHelper,
		ScopeConfigInterface $scopeConfig,
		array $data = []
	) {
		parent::__construct($context, $paymentConfig, $data);
		$this->sessionQuote = $sessionQuote;
		$this->gatewayConfig = $gatewayConfig;
		$this->ccType = $ccType;
		$this->paymentDataHelper = $paymentDataHelper;
		$this->scopeConfig = $scopeConfig;
	}

	/**
	 * Get list of available card types of order billing address country
	 * @return array
	 */
	public function getCcAvailableTypes()
	{
		$configuredCardTypes = $this->getConfiguredCardTypes();
		return $configuredCardTypes;
	}

	/**
	 * Get card types available for Braintree
	 * @return array
	 */
	private function getConfiguredCardTypes()
	{
		$types = $this->ccType->getCcTypeLabelMap();
		$ccTypes = $this->scopeConfig->getValue('payment/api_secure_trading/cctypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$ccTypes = !empty($ccTypes) ? explode(',', $ccTypes) : [];
		$configCardTypes = array_fill_keys(
			$ccTypes,
			''
		);
		return array_intersect_key($types, $configCardTypes);
	}

	public function getMonths(){
		return $this->getCcMonths();
	}

	public function getYears(){
		return $this->getCcYears();
	}

	public function getPaymentAction(){
		return $this->scopeConfig->getValue('payment/api_secure_trading/payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
}
