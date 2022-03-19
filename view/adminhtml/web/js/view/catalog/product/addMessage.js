define([
    'jquery',
    "underscore",
    'uiComponent',
    'ko',
    'domReady!'
], function ($, _, Component, ko) {
    'use strict';
    alert("hello");
    return Component.extend({
        defaults: {
            template: 'SecureTrading_Trust/addMessage'
        },
        initialize: function () {
            var self = this;
            alert("hello");
        },
    });
});
