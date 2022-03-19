define(
    [
        'jquery',
        'SecureTrading_Trust/js/view/payment/method-renderer/api-secure-trading-vault',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
    ], function ($, VaultComponent, globalMessageList, fullScreenLoader) {
        'use strict';

        return VaultComponent.extend({
            defaults: {
                modules: {
                    hostedFields: '${ $.parentName }.secure_trading'
                },
            },
        });
    });
