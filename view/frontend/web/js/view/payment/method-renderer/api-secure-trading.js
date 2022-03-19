define(
    [
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'SecureTrading_Trust/js/model/secure-trading-order',
        'Magento_Ui/js/modal/confirm',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/totals',
        'ko',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'SecureTrading_Trust/js/lib/v3/st',
        'mage/cookies',
    ],
    function (
        $,
        additionalValidators,
        trustOrder,
        confirmation,
        Component,
        totals,
        ko,
        urlBuilder,
        $t,
        redirectOnSuccessAction,
        customer,
        customerData,
        selectPaymentMethodAction,
        checkoutData,
        quote,
        SecureTrading
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SecureTrading_Trust/payment/api-secure-trading',
                checkRender: ko.observable(true),
                CheckValApi: ko.observable(0),
                orderId: ko.observable(),
                st: ko.observable()
            },
            getLogoUrl: function () {
                if (!parseInt(window.checkoutConfig.payment[this.getCode()].enable_api_secure_trading_logo)) {
                    return false;
                }
                return window.checkoutConfig.payment[this.getCode()].api_secure_trading_logo;
            },
            initSecuretrading: function (orderId) {
                var self = this;
                var config = this.getConfig();
                var livestatus = parseInt(config.is_test) ? 0 : 1;
                $.ajax({
                    url: window.checkoutConfig.payment.api_secure_trading.generateJwt,
                    dataType: "json",
                    type: 'GET',
                    showLoader: true,
                    data: {
                        currencyiso3a: config.currencyiso3a,
                        sitereference: config.sitereference,
                        accounttypedescription: config.accounttypedescription,
                        orderreference: orderId
                    },
                }).done(function (response) {
                    var st = SecureTrading({
                        jwt: response.jwt,
                        animatedCard: true,
                        livestatus: livestatus,
                        buttonId: 'pay',
                    });
                    st.Components();

                    if (!$('.separator').length) {

                        if(self.enablePayMentPayPal()){
                            $('.payment-method-paypal').show();
                        }

                        if(config.activevisacheckout === "1"){
                            $('#st-visa-checkout').show();
                            st.VisaCheckout({
                                buttonSettings: {
                                    size: '154',
                                    color: 'neutral'
                                },
                                merchantId: config.merchantid,
                                paymentRequest: {
                                    "currencyCode": config.currencyiso3a,
                                    "subtotal": response.mainamount,
                                    "total":  response.mainamount,
                                },
                                placement: 'st-visa-checkout',
                                settings: {
                                    displayName: config.namesite
                                }
                            });
                        }
                        if(config.activeapplepay === "1") {
                            $('#st-apple-pay').show();
                            st.ApplePay({
                                buttonStyle: 'white-outline',
                                buttonText: 'buy',
                                merchantId: config.applemerchantid,
                                paymentRequest: {
                                    countryCode: 'US',
                                    currencyCode: config.currencyiso3a,
                                    merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
                                    supportedNetworks: ["visa", "masterCard"],
                                    requiredBillingContactFields: ["postalAddress"],
                                    requiredShippingContactFields: ["postalAddress", "name", "phone", "email"],
                                    total: {
                                        label: 'Trust Payments Merchant',
                                        amount: response.mainamount
                                    }
                                },
                                placement: 'st-apple-pay'
                            });
                        }
                        if(parseInt(config.active_google_pay)) {
                            $('#st-google-pay').show();
                            st.GooglePay({
                                buttonOptions: {
                                    buttonRootNode: 'st-google-pay',
                                    buttonType: config.google_pay_button_type,
                                    buttonColor: config.google_pay_button_color,
                                    buttonLocale: config.locale_code
                                },
                                paymentRequest: {
                                    allowedPaymentMethods: [{
                                        parameters: {
                                            allowedAuthMethods: ["PAN_ONLY","CRYPTOGRAM_3DS"],
                                            allowedCardNetworks: config.google_pay_payment_type
                                        },
                                        tokenizationSpecification: {
                                            parameters: {
                                                gateway: "trustpayments",
                                                gatewayMerchantId: config.sitereference
                                            },
                                            type: "PAYMENT_GATEWAY"
                                        },
                                        type: "CARD"
                                    }],
                                    environment: parseInt(config.is_test) ? "TEST" : "PRODUCTION",
                                    apiVersion: 2,
                                    apiVersionMinor: 0,
                                    merchantInfo: {
                                        merchantName: config.google_pay_merchant_name,
                                        merchantId: config.google_pay_merchant_id
                                    },
                                    transactionInfo: {
                                        countryCode: config.country_code,
                                        currencyCode:  config.currencyiso3a,
                                        checkoutOption: "COMPLETE_IMMEDIATE_PURCHASE",
                                        totalPriceStatus: "FINAL",
                                        totalPrice: response.mainamount
                                    }
                                }
                            });
                        }
                        if(parseInt(config.active_zip_payment) && response.can_use_zip) {
                            $('#st-zip-pay').show();
                            st.APM({
                                placement: 'st-zip-pay',
                                apmList: [
                                    {
                                        name: 'ZIP',
                                        returnUrl: urlBuilder.build('securetrading/apisecuretrading/ZipPaymentReturnUrl'),
                                        minBaseAmount: config.min_amount_zip_payment,
                                        maxBaseAmount: config.max_amount_zip_payment
                                    }
                                ],
                            });
                        }
                        self.st(st);
                        if(config.activevisacheckout === "1" || (config.activeapplepay === "1" && $('#st-apple-pay').html()) || self.enablePayMentPayPal()){
                            $("div[id=separator]").append('<div class="separator">OR</div>');
                        }
                    }
                }).fail(function (err) {
                    console.log(err);
                });
            },
            getConfig: function(){
                return {
                    "grandTotal" : parseFloat(totals.totals()['base_grand_total']),
                    "currencyiso3a": window.checkoutConfig.payment.api_secure_trading.currencyiso3a,
                    "sitereference": window.checkoutConfig.payment.api_secure_trading.sitereference,
                    "accounttypedescription": window.checkoutConfig.payment.api_secure_trading.accounttypedescription,
                    "accountcheck": window.checkoutConfig.payment.api_secure_trading.accountcheck,
                    "activevisacheckout": window.checkoutConfig.payment.api_secure_trading.active_visa_checkout,
                    "merchantid": window.checkoutConfig.payment.api_secure_trading.merchant_id,
                    "namesite": window.checkoutConfig.payment.api_secure_trading.name_site,
                    "activeapplepay": window.checkoutConfig.payment.api_secure_trading.active_apple_pay,
                    "applemerchantid": window.checkoutConfig.payment.api_secure_trading.apple_merchant_id,
                    "is_test": window.checkoutConfig.payment.api_secure_trading.is_test,
                    "active_google_pay": window.checkoutConfig.payment.api_secure_trading.active_google_pay,
                    "google_pay_merchant_id": window.checkoutConfig.payment.api_secure_trading.google_pay_merchant_id,
                    "google_pay_merchant_name": window.checkoutConfig.payment.api_secure_trading.google_pay_merchant_name,
                    "google_pay_payment_type": window.checkoutConfig.payment.api_secure_trading.google_pay_payment_type,
                    "google_pay_button_type": window.checkoutConfig.payment.api_secure_trading.google_pay_button_type,
                    "google_pay_button_color": window.checkoutConfig.payment.api_secure_trading.google_pay_button_color,
                    "country_code": window.checkoutConfig.payment.api_secure_trading.country_code,
                    "locale_code": window.checkoutConfig.payment.api_secure_trading.locale_code,
                    "active_zip_payment": window.checkoutConfig.payment.api_secure_trading.active_zip_payment,
                    "min_amount_zip_payment": window.checkoutConfig.payment.api_secure_trading.min_amount_zip_payment,
                    "max_amount_zip_payment": window.checkoutConfig.payment.api_secure_trading.max_amount_zip_payment,
                    };
            },
            getCode: function () {
                return 'api_secure_trading';
            },

            getCardUrl: function () {
                return urlBuilder.build('securetrading/apisecuretrading/cardurl');
            },

            getData: function () {
                var dataAlePay = $("#placeOrder").val();
                var data = {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_action': window.checkoutConfig.payment.api_secure_trading.payment_action,
                        'api_secure_trading_data': dataAlePay,
                        'save_card_info_api': this.CheckValApi()
                    }
                };

                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

                return data;
            },

            saveCardInfoTitle: function() {
                var self = this;

                var title = window.checkoutConfig.payment[self.getCode()].saveTitleQuestion;

                if (!title)
                    title = "Save Card Information";

                return title
            },

            checkIsSaveCardInfo: function() {
                var self = this;

                if (window.checkoutConfig.payment[self.getCode()].isSaveCardInfo != 1)
                    return false;

                return customer.isLoggedIn();
            },

            onCheckedChange: function () {
                var self = this;
                if(this.CheckValApi() === 0)
                    this.CheckValApi(1);
                else
                    this.CheckValApi(0);
                $.ajax({
                    url: urlBuilder.build('securetrading/apisecuretrading/saveCardInfo'),
                    dataType: "json",
                    type: 'POST',
                    showLoader: true,
                    data: {
                        order_id: trustOrder.getOrderId(),
                        is_save: this.CheckValApi(),
                    },
                }).done(function (response) {
                    var jwt = response.jwt;
                    var st = self.st();
                    st.updateJWT(jwt);
                });
            },
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.getPlaceOrderDeferredObject()
                        .done(function (orderID) {
                            trustOrder.setOrderID(orderID);
                            self.orderId(orderID);
                            var id = self.getCode();
                            self.disableRadioPayment(id);
                            $('input[type="checkbox"]#agreement_api_secure_trading_1').attr("disabled", true);
                            $('button#placeOrder').attr("disabled", true);
                            if(!$("form[class="+id+"] #st-security-code").length){
                                $("<div id='st-notification-frame'></div>").insertBefore($(".wallet-button"));
                                $("form[class="+id+"]").attr('id','st-form');
                                $("form[class="+id+"]").append(
                                    "<div class='st-wallet-button'></div>"+
                                    "<div id='st-card-number' class='st-card-number'></div>" +
                                    "<div id='security_expiry_container'>\n" +
                                    "                <div id='st-expiration-date' class='half-width float-left padding-right'></div>\n" +
                                    "                <div id='st-security-code' class='half-width float-left'></div>\n" +
                                    "            </div>"+
                                    "<div class='clear-both'></div>\n" +
                                    " <button type='submit' id='pay' class='st-form__submit'>Pay securely</button>\n" +
                                    "</form>");
                                var check = self.checkRenderAnimatedCard();
                                if(check){
                                    $(".separator").show();
                                    $("div[class=clear-both]").append(
                                        "<div id='st-animated-card' class='st-animated-card-wrapper' style='overflow-y: hidden;' ></div>");
                                }
                            };
                            $("#restore-quote").show();
                            $("#save-card-info-api").show();
                            if($("#st-animated-card").length){
                                $("#st-animated-card").css("height", '330px');
                            }
                            self.initSecuretrading(orderID);
                        });
                    return true;
                }
                return false;
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
            getUrlToPayPal: function () {
                var self = this;
                if (self.orderId()) {
                    var url = urlBuilder.build('securetrading/apisecuretrading/GetUrlRedirectPayPal');
                    $('body').trigger("processStart");
                    $.ajax({
                        url: url,
                        dataType: "json",
                        type: 'GET',
                        showLoader: true,
                        data: {
                            orderId: self.orderId()
                        },
                    }).done(function (response) {
                        window.location.href = response.redirecturl;
                    });
                    setTimeout(function () {
                        $('body').trigger("processStop");
                    }, 4000);
                } else {
                    this.getPlaceOrderDeferredObject()
                        .done(function (orderID) {
                            var url = urlBuilder.build('securetrading/apisecuretrading/GetUrlRedirectPayPal');
                            $('body').trigger("processStart");
                            $.ajax({
                                url: url,
                                dataType: "json",
                                type: 'GET',
                                showLoader: true,
                                data: {
                                    orderId: orderID
                                },
                            }).done(function (response) {
                                window.location.href = response.redirecturl;
                            });
                            setTimeout(function () {
                                $('body').trigger("processStop");
                            }, 4000);
                        });
                }
            },
            selectPaymentMethod: function () {
                var self = this;
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                if($(".vault #st-security-code").length){
                    $(".vault #st-form").empty();
                }
                return true;
            },
            checkRenderAnimatedCard: function () {
                var render = window.checkoutConfig.payment.api_secure_trading.animated_card;
                if(render != 1) return false;
                return true;
            },
            disableRadioPayment: function (id) {
                // loop through list of radio buttons
                $('input[name="payment[method]"]').each(function () {
                    if (this.value !== id) {
                        this.checked = false; // unchecked
                        this.disabled = true; // disable
                    }
                });
            },
            enablePayMentPayPal: function () {
                var check = window.checkoutConfig.payment.api_secure_trading.active_paypal_payment;
                (check === "0") ? check = false : check = true;
                var data = window.checkoutConfig.quoteItemData[0];
                var options = data.options;
                if (options.length) {
                    $.each(options, function (key, value) {
                        if (check === false || value.label === "Transaction Details") {
                            check = false;
                        } else check = true;
                    });
                }
                return check;
            },
            checkSaveCard: function () {
                var self = this;
                if (this.checkIsSaveCardInfo()) {
                    confirmation({
                        title: 'Accept save your card',
                        content: 'Do you want to save your card?',
                        actions: {
                            confirm: function () {
                                self.CheckValApi(1);
                                self.placeOrder()
                            },
                            cancel: function () {
                                self.CheckValApi(0);
                                self.placeOrder()
                            }
                        }
                    });
                } else {
                    this.placeOrder()
                }
            }
        });
    }
);
