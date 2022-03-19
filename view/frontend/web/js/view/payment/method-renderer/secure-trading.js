define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Customer/js/model/customer',
        'ko'
    ],
    function (
        $,
        Component,
        additionalValidators,
        redirectOnSuccessAction,
        customer,
        ko
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SecureTrading_Trust/payment/secure-trading',
                CheckVal: ko.observable(0),
            },

            getLogoUrl: function () {
                if (!parseInt(window.checkoutConfig.payment[this.getCode()].enable_payment_pages_logo)) {
                    return false;
                }
                return window.checkoutConfig.payment[this.getCode()].payment_pages_logo;
            },

            getData: function() {
                if (isNaN($('#save_card_info').val()))
                    $('#save_card_info').val(0);
                var data = {
                    'method': this.item.method,
                    'additional_data': {
                        'save_card_info': $('#save_card_info').val()
                    }
                };

                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

                return data;
            },

            initObservable: function() {
                this._super()
                    .observe('CheckVal');
                return this;
            },

            getCode: function () {
                return 'secure_trading';
            },

            onCheckedChange: function () {
                if(this.CheckVal() == 0)
                    this.CheckVal(1);
                else
                    this.CheckVal(0);
            },

            checkIsSaveCardInfo: function() {
                var self = this;

                if (window.checkoutConfig.payment[self.getCode()].isSaveCardInfo != 1)
                    return false;

                return customer.isLoggedIn;
            },

            saveCardInfoTitle: function() {
                var self = this;

                var title = window.checkoutConfig.payment[self.getCode()].saveTitleQuestion;

                if (!title)
                    title = "Save Card Information";

                return title
            },

            getInstructions: function () {
                var self = this;
                if(window.checkoutConfig.payment[self.getCode()].instruction != null){
                    return window.checkoutConfig.payment[self.getCode()].instruction;
                }
                return '';
            },
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        }).done(function (orderID) {
                            var isFrame = window.checkoutConfig.payment[self.getCode()].isIframe;
                            if (isFrame == "1") {
                                var $form = $("<form>")
                                    .attr("method", "POST")
                                    .attr("action", window.checkoutConfig.payment[self.getCode()].iframeUrl);
                                $form.append($("<input type='hidden'>").attr("name", "orderId").val(orderID));
                                $('body').append($form);
                                $form.submit();
                            } else {
                                $.ajax({
                                    url: window.checkoutConfig.payment[self.getCode()].startUrl,
                                    dataType: "json",
                                    type: 'POST',
                                    showLoader: true,
                                    data: {
                                        order_id: orderID
                                    }
                                }).done(function (response) {
                                    var info = response.info,
                                        $form = $("<form>")
                                            .attr("method", "POST")
                                            .attr("action", response.url);
                                    $form.append($("<input type='hidden'>").attr("name", "_charset_"));
                                    $.each(info, function (key, value) {
                                        // if (key == 'ruleidentifiers') {
                                        //     $.each(value, function (key, value) {
                                        //         var $hiddenField = $("<input type='hidden'>")
                                        //             .attr("name", "ruleidentifier")
                                        //             .val(value);
                                        //         $form.append($hiddenField);
                                        //     })
                                        // }
                                        // else if (key == 'stextraurlnotifyfields') {
                                        //     $.each(value, function (key, value) {
                                        //         var $hiddenField = $("<input type='hidden'>")
                                        //             .attr("name", "stextraurlnotifyfields")
                                        //             .val(value);
                                        //         $form.append($hiddenField);
                                        //     })
                                        // }
                                        if($.isArray(value)){
                                            $.each(value, function (k, v) {
                                                        var $hiddenField = $("<input type='hidden'>")
                                                            .attr("name", key)
                                                            .val(v);
                                                        $form.append($hiddenField);
                                                    })
                                        }
                                        else {
                                            var $hiddenField = $("<input type='hidden'>")
                                                .attr("name", key)
                                                .val(value);
                                            $form.append($hiddenField);
                                        }
                                    });
                                    $('body').append($form);
                                    $form.submit();
                                }).fail(function (err) {
                                    console.log(err);
                                });
                            }
                            self.afterPlaceOrder();
                        }
                    );
                    return true;
                }
                return false;
            },
        });
    }
);