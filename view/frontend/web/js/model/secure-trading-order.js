define([
    'ko'
], function (ko) {
    'use strict';

    return {
        orderId: ko.observable(null),
        methodId: ko.observable(null),

        getOrderId: function () {
            return this.orderId();
        },

        setOrderID: function (id) {
            return this.orderId(id)
        },
        setMethodId: function (id) {
            return this.methodId(id);
        },

        getMethodId: function () {
            return this.methodId()
        }
    };
});
