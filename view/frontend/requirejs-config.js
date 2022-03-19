var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping-information': {
                'SecureTrading_Trust/js/view/shipping-information-mixin': true
            },
            'Magento_Checkout/js/view/progress-bar': {
                'SecureTrading_Trust/js/view/progress-bar-mixin': true
            }
        }
    }
};