define(
    [
        'jquery',
        'SecureTrading_Trust/js/model/secure-trading-order',
        'mage/url',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/sidebar',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, trustOrder, urlBuilder, stepNavigator, sidebarModel, customerData, quote) {
        'use strict';
        var mixins = {
            back: function () {
                var orderId = trustOrder.getOrderId();
                if (orderId) {
                    this.restoreQuote(orderId);
                } else {
                    sidebarModel.hide();
                    stepNavigator.navigateTo('shipping');
                    $('input[name="payment[method]"]').each(function () {
                        this.checked = false;
                        this.disabled = false;
                    });
                }
            },

            backToShippingMethod: function () {
                var orderId = trustOrder.getOrderId();
                if (orderId) {
                    this.restoreQuote(orderId);
                } else {
                    sidebarModel.hide();
                    stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                    $('input[name="payment[method]"]').each(function () {
                        this.checked = false;
                        this.disabled = false;
                    });
                }
            },

            restoreQuote : function (orderId){
                $.ajax({
                    url: urlBuilder.build('securetrading/apisecuretrading/restorequote'),
                    dataType: "json",
                    type: 'POST',
                    showLoader: true,
                    data: {
                        orderId: orderId
                    },
                }).done(function (response) {
                    var methodId = trustOrder.getMethodId();
                    var iframe = $('.api_secure_trading iframe');
                    iframe.each((key) => {
                        iframe[key].remove();
                    });
                    if($(".vault #st-security-code").length){
                        $('#vault-payment-method-billing-address-'+methodId).show();
                        $('#st-notification-frame').remove()
                        $("button[id="+methodId+"]").show();
                        $("#restore-quote-"+methodId).hide();
                        iframe = $('.vault iframe');
                        iframe.each((key) => {
                            iframe[key].remove();
                        });
                        if($(".vault #st-security-code").length){
                            $(".vault #st-form").empty();
                            $("form[class="+methodId+"]").removeAttr('id');
                        }
                    }
                    quote.paymentMethod(null);
                    var sections = ['cart'];
                    customerData.invalidate(sections);
                    customerData.reload(sections, true);
                    sidebarModel.hide();
                    stepNavigator.navigateTo('shipping');
                    $('input[name="payment[method]"]').each(function () {
                        this.checked = false;
                        this.disabled = false;
                    });
                });
            }
        };

        return function (target) {
            return target.extend(mixins);
        }
});
