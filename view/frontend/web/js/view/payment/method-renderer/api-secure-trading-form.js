
define([
    'underscore',
    'jquery',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'mage/translate',
    'Magento_Checkout/js/action/redirect-on-success'
], function (_, $, customer, Component, creditCardData, cardNumberValidator, $t, redirectOnSuccessAction) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'SecureTrading_Trust/payment/api-secure-trading',
            active: false,
            creditCardType: '',
            creditCardExpYear: '',
            creditCardExpMonth: '',
            creditCardNumber: '',
            creditCardSsStartMonth: '',
            creditCardSsStartYear: '',
            creditCardSsIssue: '',
            creditCardVerificationNumber: '',
            selectedCardType: null,
            CheckValApi: 0,
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardNumber',
                    'creditCardVerificationNumber',
                    'creditCardSsStartMonth',
                    'creditCardSsStartYear',
                    'creditCardSsIssue',
                    'selectedCardType',
                    'active',
                    'CheckValApi'
                ]);

            return this;
        },

        /**
         * Init component
         */
        initialize: function () {
            var self = this;

            this._super();

            //Set credit card number to credit card data object
            this.creditCardNumber.subscribe(function (value) {
                var result;

                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.creditCardNumber = value;
                    self.creditCardType(result.card.type);
                }
            });

            //Set expiration year to credit card data object
            this.creditCardExpYear.subscribe(function (value) {
                creditCardData.expirationYear = value;
            });

            //Set expiration month to credit card data object
            this.creditCardExpMonth.subscribe(function (value) {
                creditCardData.expirationMonth = value;
            });

            //Set cvv code to credit card data object
            this.creditCardVerificationNumber.subscribe(function (value) {
                creditCardData.cvvCode = value;
            });
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode: function () {
            return 'api_secure_trading';
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {
            var data = {
                'method': this.item.method,
                'additional_data': {
                    'payment_action': window.checkoutConfig.payment.api_secure_trading.payment_action,
                    'security_code': this.creditCardVerificationNumber(),
                    'cc_ss_start_month': this.creditCardSsStartMonth(),
                    'cc_ss_start_year': this.creditCardSsStartYear(),
                    'cc_ss_issue': this.creditCardSsIssue(),
                    'cc_type': this.creditCardType(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_number': this.creditCardNumber(),
                    'save_card_info_api': $('#save_card_info_api').val()
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment.api_secure_trading.availableTypes['api_secure_trading'];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment.ccform.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.ccform.icons[type]
                : false;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths: function () {
            return window.checkoutConfig.payment.api_secure_trading.months[this.getCode()];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears: function () {
            return window.checkoutConfig.payment.api_secure_trading.years[this.getCode()];
        },

        /**
         * Check if current payment has verification
         * @returns {Boolean}
         */
        hasVerification: function () {
            return window.checkoutConfig.payment.api_secure_trading.hasVerification[this.getCode()];
        },

        /**
         * @deprecated
         * @returns {Boolean}
         */
        hasSsCardType: function () {
            return window.checkoutConfig.payment.api_secure_trading.hasSsCardType[this.getCode()];
        },

        /**
         * Get image url for CVV
         * @returns {String}
         */
        getCvvImageUrl: function () {
            return window.checkoutConfig.payment.api_secure_trading.cvvImageUrl[this.getCode()];
        },

        /**
         * Get image for CVV
         * @returns {String}
         */
        getCvvImageHtml: function () {
            return '<img src="' + this.getCvvImageUrl() +
                '" alt="' + $t('Card Verification Number Visual Reference') +
                '" title="' + $t('Card Verification Number Visual Reference') +
                '" />';
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYears: function () {
            return window.checkoutConfig.payment.api_secure_trading.ssStartYears;
        },

        /**
         * Get list of available credit card types values
         * @returns {Object}
         */
        getCcAvailableTypesValues: function () {
            return _.map(this.getCcAvailableTypes(), function (value, key) {
                return {
                    'value': key,
                    'type': value
                };
            });
        },

        /**
         * Get list of available month values
         * @returns {Object}
         */
        getCcMonthsValues: function () {
            return _.map(this.getCcMonths(), function (value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        /**
         * Get list of available year values
         * @returns {Object}
         */
        getCcYearsValues: function () {
            return _.map(this.getCcYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYearsValues: function () {
            return _.map(this.getSsStartYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * Is legend available to display
         * @returns {Boolean}
         */
        isShowLegend: function () {
            return false;
        },

        /**
         * Get available credit card type by code
         * @param {String} code
         * @returns {String}
         */
        getCcTypeTitleByCode: function (code) {
            var title = '',
                keyValue = 'value',
                keyType = 'type';

            _.each(this.getCcAvailableTypesValues(), function (value) {
                if (value[keyValue] === code) {
                    title = value[keyType];
                }
            });

            return title;
        },

        /**
         * Prepare credit card number to output
         * @param {String} number
         * @returns {String}
         */
        formatDisplayCcNumber: function (number) {
            return 'xxxx-' + number.substr(-4);
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardNumber())
                }
            ];
        },
        isActive: function () {
            var active = this.getCode() === this.isChecked();

            this.active(active);

            return active;
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
        onCheckedChange: function () {
            if(this.CheckValApi() === 0)
                this.CheckValApi(1);
            else
                this.CheckValApi(0);
        },
        placeOrder: function () {
            var self = this;
            this.getPlaceOrderDeferredObject()
                .done(
                    function () {
                        self.afterPlaceOrder();

                        if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    }
                ).always(
                function () {
                    self.isPlaceOrderActionAllowed(true);
                }
            );
        },
    });
});
