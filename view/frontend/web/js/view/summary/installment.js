define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
    ],
    function (Component, quote, priceUtils) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'SecureTrading_Trust/summary/installment'
            },
            title: 'Amount',
            logo: 'SecureTrading_Trust/images/tp-logo.png',
            totals: quote.getTotals(),
            installment: window.checkoutConfig.installment,
            isDisplayed: function () {
                if(this.installment){
                    return true;
                }
                return false;
            },
            getValue: function () {
                var price = 0,
                    subscriptionFinalNumber = parseInt(this.installment.subscriptionfinalnumber),
                    skipTheFirstPayment = parseInt(this.installment.skipthefirstpayment),
                    style = this.installment.subscriptiontype;
                if (this.totals()) {
                    if(style === "INSTALLMENT"){
                        price = parseFloat(this.totals()['grand_total'] / (subscriptionFinalNumber - skipTheFirstPayment));
                    } else if(style === "RECURRING"){
                        price = parseFloat(this.totals()['grand_total']);
                    }
                }
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },
            getBaseValue: function () {
                var basePrice = 0,
                    subscriptionFinalNumber = parseInt(this.installment.subscriptionfinalnumber),
                    skipTheFirstPayment = parseInt(this.installment.skipthefirstpayment);
                if (this.totals()) {
                    basePrice = parseFloat(this.totals()['base_grand_total'] / (subscriptionFinalNumber - skipTheFirstPayment));
                }
                return '['+priceUtils.formatPrice(basePrice, quote.getBasePriceFormat())+']';
            },
            getDescription: function () {
                var freeTrial = parseInt(this.installment.skipthefirstpayment),
                    frequency = parseInt(this.installment.subscriptionfrequency),
                    unit = this.installment.subscriptionunit,
                    finalNumber = parseInt(this.installment.subscriptionfinalnumber),
                    style = this.installment.subscriptiontype;
                unit = unit.toLowerCase();
                unit = unit.charAt(0).toUpperCase()+unit.slice(1);
                style = style.toLowerCase();
                style = style.charAt(0).toUpperCase()+style.slice(1);
                if(freeTrial){
                    return this.getValue()+' every '+frequency+' '+unit+'(s) processing '+finalNumber+' '+style+' payments in total (Free Trial - first '+unit+' is free.)';
                }else {
                    return this.getValue()+' every '+frequency+' '+unit+'(s) processing '+finalNumber+' '+style+' payments in total';
                }
            },
        });
    }
);