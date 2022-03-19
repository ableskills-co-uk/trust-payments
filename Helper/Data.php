<?php

namespace SecureTrading\Trust\Helper;


use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 *
 * @package SecureTrading\Trust\Helper
 */
class Data extends AbstractHelper
{
    const API_MEHTOD               = 'api_secure_trading';
    const ACTION_AUTHORIZE         = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';
    const CHOICE_PAGE              = 'url_choice_page';
    const DETAILS_PAGE             = 'url_details_page';
    const SKIP_CHOICE_PAGE         = 'payment_pages/payment_pages_optional/version_settings/skip_choice_page';
    const ST_PROFILE               = 'payment_pages/payment_pages_optional/version_settings/st_profile';
    const CURRENCY                 = 'currency';
    const SITE_REFERENCE           = 'site_reference';
    const VERSION                  = 'payment_pages/payment_pages_optional/version_settings/version';
    const SETTLE_DUE_DATE          = 'payment_pages/payment_pages_optional/other_settings/settle_due_date';
    const API_SETTLE_DUE_DATE      = 'payment/api_secure_trading/api_settle_due_date';
    const SITE_PASS                = 'site_password';
    const USE_IFRAME               = 'payment_pages/payment_pages_optional/display_settings/use_iframe';
    const IFRAME_HEIGHT            = 'payment_pages/payment_pages_optional/display_settings/iframe_height';
    const IFRAME_WIDTH             = 'payment_pages/payment_pages_optional/display_settings/iframe_width';
    const PAYMENT_ACTION           = 'payment_pages/payment_pages_optional/other_settings/payment_action';
    const SETTLE_STATUS            = 'payment_pages/payment_pages_optional/other_settings/settle_status';
    const BACK_OFFICE              = 'back_office';
    const USER_NAME                = 'username';
    const PASSWORD                 = 'password';
    const ORDER_STATUS             = 'pending_secure_trading_payment';
    const ORDER_STATUS_LABEL       = 'Payment Pages';
    const DESCRIPTION              = 'payment_pages/payment_pages_optional/display_settings/description';
    const IS_TOKENIZATION          = 'payment/vault_secure_trading/active';
    const IS_TOKENIZATION_API      = 'payment/api_secure_trading/active';
    const JWT_NAME                 = 'jwt_name';
    const JWT_SECRET_KEY           = 'jwt_secret_key';
    const SAVE_TITLE_QUESTION      = 'payment/secure_trading/payment_pages/payment_pages_optional/tokenization_settings/save_cc_question';
    const SAVE_TITLE_QUESTION_API  = 'payment/api_secure_trading/payment_pages/payment_pages_optional/tokenization_settings/save_cc_question';
    const RECUR_ACC_TYPE           = 'RECUR';
    const ECOM_ACC_TYPE            = 'ECOM';
    const ACCOUNT_CHECK_TYPE       = 'ACCOUNTCHECK';
    const AUTH_CHECK_TYPE          = 'AUTH';
    const ATT_ENABLE_SUBS          = 'stpp_enable_subs';
    const ATT_REQUIRE_SUBS         = 'stpp_require_subs';
    const ATT_OPTIONS_SUBS         = 'stpp_options_subs';
    const END_POINT                = 'payment/secure_trading/endpoint';
    const ACCOUNT_CHECK            = 'payment/secure_trading/account_check';
    const ANIMATED_CARD            = 'payment/api_secure_trading/animated_card';
    const IS_VISACHECKOUT          = 'payment/api_secure_trading/active_visa_checkout';
    const MERCHANT_ID              = 'payment/api_secure_trading/merchant_id';
    const IS_PAYPALPAYMENT         = 'payment/api_secure_trading/active_paypal_payment';
    const PAYPAL_MERCHANT_ID       = 'payment/api_secure_trading/paypal_merchant_id';
    const NAME_SITE                = 'payment/api_secure_trading/name_site';
    const API_PAYMENT_ACTION       = 'payment_action';
    const API_SETTLE_STATUS        = 'payment/api_secure_trading/api_settle_status';
    const IS_APPLE_PAY             = 'payment/api_secure_trading/active_apple_pay';
    const APPLE_MERCHANT_ID        = 'payment/api_secure_trading/apple_merchant_id';
    const IS_TEST_API              = 'payment/api_secure_trading/is_test';
    const IS_ACTIVE_GOOGLE_PAY     = 'payment/api_secure_trading/active_google_pay';
    const GOOGLE_PAY_MERCHANT_ID   = 'payment/api_secure_trading/google_merchant_id';
    const GOOGLE_PAY_MERCHANT_NAME = 'payment/api_secure_trading/google_merchant_name';
    const GOOGLE_PAY_PAYMENT_TYPE  = 'payment/api_secure_trading/google_payment_type';
    const GOOGLE_PAY_BUTTON_TYPE = 'payment/api_secure_trading/google_pay_button_type';
    const GOOGLE_PAY_BUTTON_COLOR = 'payment/api_secure_trading/google_pay_button_color';
    const IS_ACTIVE_ZIP_PAYMENT     = 'payment/api_secure_trading/active_zip_payment';
    const MIN_AMOUNT_ZIP_PAYMENT     = 'payment/api_secure_trading/min_amount_zip_payment';
    const MAX_AMOUNT_ZIP_PAYMENT     = 'payment/api_secure_trading/max_amount_zip_payment';
}