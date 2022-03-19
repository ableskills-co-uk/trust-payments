define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'mage/url',
        'SecureTrading_Trust/js/lib/v3/st',
    ],
    function (
        $,
        Component,
        ko,
        urlBuilder,
        SecureTrading
    ) {
        'use strict';

        $(window).load(function(){
            $("#Continue").click();
        });

        return Component.extend({
            defaults: {
                template: 'SecureTrading_Trust/payment/multishipping-form-cc',
                checkRender: ko.observable(true),
                CheckValApi: ko.observable(0),
                orderId: ko.observable,
            },
            // initialize: function () {
            //     this._super();
            //     this._super();
            //     var self = this;
            //    this.buttonFake();
            // },
            initSecuretrading: function (orderId) {
                var self = this;
                $("#Continue").hide();
                var grandTotal = this.orderData.grandTotal;
                var currencyiso3a = this.orderData.currencyiso3a;
                var sitereference = this.orderData.sitereference;
                var accounttypedescription = this.orderData.accounttypedescription;
                var accountcheck = this.orderData.accountcheck;
                var activevisacheckout = this.orderData.active_visa_checkout;
                var merchantid = this.orderData.merchant_id;
                var activeapplepay = this.orderData.active_apple_pay;
                var applemerchantid = this.orderData.apple_merchant_id;
                var namesite = this.orderData.name_site;
                var livestatus = (this.orderData.is_test) ? 0 : 1;
                $.ajax({
                    url: self.orderData.generateJwt,
                    dataType: "json",
                    type: 'GET',
                    showLoader: true,
                    data: {
                        grandTotal: grandTotal,
                        currencyiso3a: currencyiso3a,
                        sitereference: sitereference,
                        accounttypedescription: accounttypedescription,
                        orderreference: orderId
                    },
                }).done(function (response) {
                    var st = SecureTrading({
                        jwt: response.jwt,
                        animatedCard: true,
                        livestatus: livestatus,
                    });
                    st.Components();

                    if(activeapplepay === "1" || activevisacheckout === "1" || self.enablePayMentPayPal()){
                        $("div[id=separator]").append('<div class="separator">OR</div>');
                    }

                    if(self.enablePayMentPayPal()){
                        $('.payment-method-paypal').show();
                    }

                    if(activevisacheckout === "1"){
                        $('#st-visa-checkout').show();
                        st.VisaCheckout({
                            buttonSettings: {
                                size: '154',
                                color: 'neutral'
                            },
                            merchantId: merchantid,
                            paymentRequest: {
                                "currencyCode": currencyiso3a,
                                "subtotal": response.mainamount,
                                "total":  response.mainamount,
                            },
                            placement: 'st-visa-checkout',
                            settings: {
                                displayName: namesite
                            }
                        });
                    }
                    if(activeapplepay === "1") {
                        $('#st-apple-pay').show();
                        st.ApplePay({
                            buttonStyle: 'white-outline',
                            merchantId: applemerchantid,
                            buttonText: 'buy',
                            paymentRequest: {
                                countryCode: 'VN',
                                currencyCode: currencyiso3a,
                                merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
                                supportedNetworks: ["visa", "masterCard"],
                                total: {
                                    label: 'Trust Payments Merchant',
                                    amount: response.mainamount
                                }
                            },
                            placement: 'st-apple-pay'
                        });
                    }
                    $("#st-form__submit").show();
                }).fail(function (err) {
                    console.log(err);
                });
            },
            getCode: function () {
                return 'api_secure_trading';
            },
            getCardUrl: function () {
                return this.orderData.cardUrl;
            },
            buttonFake: function(){
                var self = this;
                $('body').trigger("processStart");
                setTimeout(function () {
                    self.initSecuretrading(self.orderData.orderId);
                    $('body').trigger("processStop");
                }, 4000);
            },
            checkRenderAnimatedCard: function () {
                var render = this.orderData.animated_card;
                if(render != 1) return false;
                return true;
            },
            enablePayMentPayPal: function () {
                var check = this.orderData.active_paypal_payment;
                (check === "0") ? check = false : check = true;
                return check;
            },
            getUrlToPayPal: function () {
                var self = this;
                var url = urlBuilder.build('securetrading/apisecuretrading/GetUrlRedirectPayPal');
                $('body').trigger("processStart");
                $.ajax({
                    url: url,
                    dataType: "json",
                    type: 'GET',
                    showLoader: true,
                    data: {
                        orderId: self.orderData.orderId,
                        grandTotal: self.orderData.grandTotal
                    },
                }).done(function (response) {
                    window.location.href = response.redirecturl;
                });
                setTimeout(function () {
                    $('body').trigger("processStop");
                }, 4000);
            },
        });
    }

);