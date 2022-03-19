define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'api_secure_trading',
                component: 'SecureTrading_Trust/js/view/payment/method-renderer/api-secure-trading'
            }
        );
        return Component.extend({});
    }
);