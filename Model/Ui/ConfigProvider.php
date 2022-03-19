<?php

namespace SecureTrading\Trust\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Controller\ApiSecureTrading\GenerateJwt;
use SecureTrading\Trust\Helper\Data;
use SecureTrading\Trust\Helper\SubscriptionHelper;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     *
     */
    const LOGO_DIR                       = 'magenest/secure_trading/logo/';
    /**
     *
     */
    const ENABLE_PAYMENT_PAGES_LOGO      = 'payment/secure_trading/payment_pages/payment_pages_optional/other_settings/enable_payment_pages_logo';
    /**
     *
     */
    const PAYMENT_PAGES_LOGO             = 'payment/secure_trading/payment_pages/payment_pages_optional/other_settings/payment_pages_logo';
    /**
     *
     */
    const ENABLE_API_SECURE_TRADING_LOGO = 'payment/secure_trading/api_secure_trading/api_optional/api_other_settings/enable_api_secure_trading_logo';
    /**
     *
     */
    const API_SECURE_TRADING_LOGO = 'payment/secure_trading/api_secure_trading/api_optional/api_other_settings/api_secure_trading_logo';
    /**
	 *
	 */
	const CODE = 'secure_trading';

	/**
	 *
	 */
	const VAULT_CODE = 'vault_secure_trading';

	/**
	 *
	 */
	const API_CODE = 'api_secure_trading';

	/**
	 *
	 */
	const VAULT_API_CODE = 'vault_api_secure_trading';

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @var SubscriptionHelper
	 */
	protected $subscriptionHelper;

    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * ConfigProvider constructor.
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     * @param \SecureTrading\Trust\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Payment\Model\CcConfig $ccConfig
     */
    public function __construct(
        \Magento\Framework\Locale\Resolver $localeResolver,
	    \Magento\Framework\UrlInterface $urlBuilder,
	    \Psr\Log\LoggerInterface $logger,
	    \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\Asset\Repository $assetRepository,
		StoreManagerInterface $storeManager,
		ConfigInterFace $config,
		SubscriptionHelper $subscriptionHelper,
		CcConfig $ccConfig
	) {
        $this->localeResolver     = $localeResolver;
        $this->urlBuilder         = $urlBuilder;
        $this->logger             = $logger;
        $this->request            = $request;
        $this->assetRepository    = $assetRepository;
        $this->config             = $config;
        $this->storeManager       = $storeManager;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->ccConfig           = $ccConfig;
	}

    /**
     * @param $fileId
     * @param array $params
     * @return string
     */
    private function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepository->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * @param $path
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getLogoUrl($path) {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if (!$path) {
	        return $this->getViewFileUrl('SecureTrading_Trust::images/tp-logo.png');
        }
	    return $mediaUrl.self::LOGO_DIR.$path;
    }

	/**
	 * Retrieve assoc array of checkout configuration
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return [
			'payment' => [
				self::CODE => [
					'isActive'    => $this->config->getValue('active'),
					'isIframe'    => $this->config->getValue(Data::USE_IFRAME),
					'startUrl'    => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/start',
					'iframeUrl'   => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/iframe',
					'instruction' => $this->config->getValue(Data::DESCRIPTION),
                    'isSaveCardInfo' => $this->storeManager->getStore()->getConfig(Data::IS_TOKENIZATION),
                    'saveTitleQuestion' => $this->storeManager->getStore()->getConfig(Data::SAVE_TITLE_QUESTION),
					'accountcheck' => $this->storeManager->getStore()->getConfig(Data::ACCOUNT_CHECK),
                    'enable_payment_pages_logo' => $this->storeManager->getStore()->getConfig(self::ENABLE_PAYMENT_PAGES_LOGO),
                    'payment_pages_logo' => $this->getLogoUrl($this->storeManager->getStore()->getConfig(self::PAYMENT_PAGES_LOGO)),
				],
                self::VAULT_CODE => [
                    'vaultUrl' => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/vault',
                    'jwtResponseUrl' => $this->storeManager->getStore()->getBaseUrl() . 'securetrading/paymentpage/vaultnotificationresponse',
	                'accountcheck' => $this->storeManager->getStore()->getConfig(Data::ACCOUNT_CHECK),
                ],
				self::API_CODE => [
                    'endPoint' => $this->storeManager->getStore()->getConfig(Data::END_POINT),
                    'payment_action' => $this->storeManager->getStore()->getConfig('payment/api_secure_trading/payment_action'),
                    'generateJwt' => $this->storeManager->getStore()->getBaseUrl().'securetrading/apisecuretrading/generatejwt',
                    'start' => $this->storeManager->getStore()->getBaseUrl().'securetrading/apisecuretrading/start',
                    'cardUrl' => $this->storeManager->getStore()->getBaseUrl().'securetrading/apisecuretrading/cardurl',
                    'currencyiso3a' => $this->subscriptionHelper->getCurrentCurrencyCode(),
                    'sitereference' => $this->storeManager->getStore()->getConfig(Data::IS_TEST_API) ? $this->storeManager->getStore()->getConfig(GenerateJwt::TEST_SITE) : $this->storeManager->getStore()->getConfig(GenerateJwt::SITE),
                    'accounttypedescription' => $this->subscriptionHelper->getAccountTypeDescription(),
                    'availableTypes' => [self::API_CODE => $this->ccConfig->getCcAvailableTypes()],
                    'months' => [self::API_CODE => $this->ccConfig->getCcMonths()],
                    'years' => [self::API_CODE => $this->ccConfig->getCcYears()],
                    'hasVerification' => $this->ccConfig->hasVerification(),
                    'cvvImageUrl' => [self::API_CODE => $this->ccConfig->getCvvImageUrl()],
                    'ssStartYears' => $this->ccConfig->getSsStartYears(),
                    'isSaveCardInfo' => $this->storeManager->getStore()->getConfig(Data::IS_TOKENIZATION_API),
                    'saveTitleQuestion' => $this->storeManager->getStore()->getConfig(Data::SAVE_TITLE_QUESTION),
                    'instruction' => $this->config->getValue(Data::DESCRIPTION),
                    'accountcheck' => $this->storeManager->getStore()->getConfig(Data::ACCOUNT_CHECK),
                    'animated_card' => $this->storeManager->getStore()->getConfig(Data::ANIMATED_CARD),
                    'active_visa_checkout' => $this->storeManager->getStore()->getConfig(Data::IS_VISACHECKOUT),
                    'merchant_id' => $this->storeManager->getStore()->getConfig(Data::MERCHANT_ID),
                    'name_site' => $this->storeManager->getStore()->getConfig(Data::NAME_SITE),
                    'active_apple_pay' => $this->storeManager->getStore()->getConfig(Data::IS_APPLE_PAY),
                    'apple_merchant_id' => $this->storeManager->getStore()->getConfig(Data::APPLE_MERCHANT_ID),
                    'active_paypal_payment' => $this->storeManager->getStore()->getConfig(Data::IS_PAYPALPAYMENT),
                    'active_google_pay' => $this->storeManager->getStore()->getConfig(Data::IS_ACTIVE_GOOGLE_PAY),
                    'active_zip_payment' => $this->storeManager->getStore()->getConfig(Data::IS_ACTIVE_ZIP_PAYMENT),
                    'min_amount_zip_payment' => $this->storeManager->getStore()->getConfig(Data::MIN_AMOUNT_ZIP_PAYMENT),
                    'max_amount_zip_payment' => $this->storeManager->getStore()->getConfig(Data::MAX_AMOUNT_ZIP_PAYMENT),
                    'google_pay_merchant_id' => $this->storeManager->getStore()->getConfig(Data::GOOGLE_PAY_MERCHANT_ID),
                    'country_code' =>  $this->storeManager->getStore()->getConfig('general/country/default'),
                    'google_pay_merchant_name' => $this->storeManager->getStore()->getConfig(Data::GOOGLE_PAY_MERCHANT_NAME),
                    'google_pay_payment_type' => explode(',',$this->storeManager->getStore()->getConfig(Data::GOOGLE_PAY_PAYMENT_TYPE)),
                    'google_pay_button_type' => $this->storeManager->getStore()->getConfig(Data::GOOGLE_PAY_BUTTON_TYPE),
                    'google_pay_button_color' => $this->storeManager->getStore()->getConfig(Data::GOOGLE_PAY_BUTTON_COLOR),
                    'locale_code' => $this->getCurrentLocale(),
                    'is_test' => $this->storeManager->getStore()->getConfig(Data::IS_TEST_API),
                    'enable_api_secure_trading_logo' => $this->storeManager->getStore()->getConfig(self::ENABLE_API_SECURE_TRADING_LOGO),
                    'api_secure_trading_logo' => $this->getLogoUrl($this->storeManager->getStore()->getConfig(self::API_SECURE_TRADING_LOGO)),
				]
			]
		];
	}

    /**
     * @return false|string
     */
    public function getCurrentLocale()
    {
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }
}