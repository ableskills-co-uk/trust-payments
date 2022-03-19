define(
    [
        'jquery',
        'SecureTrading_Trust/js/lib/v3/st',
        'SecureTrading_Trust/js/model/secure-trading-order',
        'ko',
        'Magento_Vault/js/view/payment/method-renderer/vault',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/totals',
        'mage/url',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators'
    ], function ($, SecureTrading, trustOrder, ko, VaultComponent, globalMessageList, fullScreenLoader, redirectOnSuccessAction, totals, urlBuilder, selectPaymentMethod, checkoutData, additionalValidators) {
        'use strict';

        return VaultComponent.extend({
            defaults: {
                template: 'SecureTrading_Trust/payment/form',
                modules: {
                    hostedFields: '${ $.parentName }.api_secure_trading'
                },
                redirectAfterPlaceOrder: false,
                orderId: ko.observable()
            },

            initSecuretradingVault: function (orderID) {
                var self = this;
                var isTest = window.checkoutConfig.payment.api_secure_trading.is_test;
                var livestatus = parseInt(isTest) ? 0 : 1;
                var grandTotal = parseFloat(totals.totals()['grand_total']);
                var currencyiso3a = window.checkoutConfig.payment.api_secure_trading.currencyiso3a;
                var sitereference = window.checkoutConfig.payment.api_secure_trading.sitereference;
                var accounttypedescription = window.checkoutConfig.payment.api_secure_trading.accounttypedescription;
                var accountcheck = window.checkoutConfig.payment.api_secure_trading.accountcheck;
                $.ajax({
                    url: window.checkoutConfig.payment.api_secure_trading.generateJwt,
                    dataType: "json",
                    type: 'GET',
                    showLoader: true,
                    data: {
                        grandTotal: grandTotal,
                        currencyiso3a: currencyiso3a,
                        sitereference: sitereference,
                        accounttypedescription: accounttypedescription,
                        parenttransactionreference: self.details.parenttransactionreference,
                        orderreference: orderID,
                        is_vault: 1,
                    },
                }).done(function (response) {
                    var st = SecureTrading({
                        jwt: response.jwt,
                        fieldsToSubmit: ['securitycode'],
                        livestatus: livestatus,
                    });
                    st.Components();

                }).fail(function (err) {
                    console.log(err);
                });
            },
            /**
             * Get last 4 digits of card
             * @returns {String}
             */
            getMaskedCard: function () {
                return this.details.maskedpan;
            },

            /**
             * Get expiration date
             * @returns {String}
             */
            getExpirationDate: function () {
                return this.details.cardExpire[0][0]+ "/" +this.details.cardExpire[0][1];
            },

            /**
             * Get card type
             * @returns {String}
             */
            getCardType: function () {
                return this.details.paymentType;
            },

            /**
             * Get payment method data
             * @returns {Object}
             */
            getData: function () {
                var data = {
                    'method': this.code,
                    'additional_data': {
                        'public_hash' : this.publicHash,
                        'account_type': this.details.accountType,
                        'save_card_info_api': false,
                    }
                };

                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

                return data;
            },
            getCardUrl: function () {
                return urlBuilder.build('securetrading/apisecuretrading/cardurl');
            },
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.getPlaceOrderDeferredObject()
                        .done(function (orderID) {
                            var id = self.getId();
                            self.orderId(orderID);
                            trustOrder.setOrderID(orderID);
                            trustOrder.setMethodId(id);
                            $('#vault-payment-method-billing-address-'+id).hide();
                            self.disableRadioPayment(id);
                            $('input[type="checkbox"]#agreement_api_secure_trading_1').attr("disabled", true);
                            $("button[id="+id+"]").hide();
                            $('#save-card').hide();
                            if(!$("form[class="+id+"] #st-security-code").length){
                                $("<div id='st-notification-frame'></div>").insertBefore($("form[class="+id+"]"));
                                $("form[class="+id+"]").attr('id','st-form');
                                $("form[class="+id+"]").append(
                                    "<div id='st-security-code' class='st-security-code' style='width: 100px;'></div>" +
                                    "<button style='display: none' type='submit' id='st-form__submit' class='st-form__submit'>Pay securely</button>" +
                                    "</form>");
                            };
                            $('#st-form__submit').show();
                            $("#restore-quote-"+id).show();
                            self.initSecuretradingVault(orderID);
                            return true;
                        });
                }
                return false;
            },
            selectPaymentMethod: function () {
                selectPaymentMethod(
                    {
                        method: this.getId()
                    }
                );
                return true;
            },
            restoreQuote: function() {
                $.ajax({
                    url: urlBuilder.build('securetrading/apisecuretrading/restorequote'),
                    dataType: "json",
                    type: 'POST',
                    showLoader: true,
                    data: {
                        orderId: this.orderId()
                    },
                }).done(function (response) {
                    window.location.reload();
                });
            },
            disableRadioPayment: function (id) {
                // loop through list of radio buttons
                $('input[name="payment[method]"]').each(function () {
                    if (this.value !== id) {
                        this.checked = false; // unchecked
                        this.disabled = true; // disable
                    }
                });
            }
        });
    });
