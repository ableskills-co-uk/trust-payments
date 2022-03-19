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
            navigateTo: function (step) {
                var orderId = trustOrder.getOrderId();
                var methodId = trustOrder.getMethodId();
                if (orderId) {
                    $.ajax({
                        url: urlBuilder.build('securetrading/apisecuretrading/restorequote'),
                        dataType: "json",
                        type: 'POST',
                        showLoader: true,
                        data: {
                            orderId: orderId
                        },
                    }).done(function (response) {
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
                        stepNavigator.navigateTo(step.code);
                        $('input[name="payment[method]"]').each(function () {
                            this.checked = false;
                            this.disabled = false;
                        });
                    });
                } else {
                    stepNavigator.navigateTo(step.code);
                    $('input[name="payment[method]"]').each(function () {
                        this.checked = false;
                        this.disabled = false;
                    });
                }
            },
        };

        return function (target) {
            return target.extend(mixins);
        }
    });
