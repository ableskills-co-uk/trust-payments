<?php


namespace SecureTrading\Trust\Model\Source;


class GooglePaymentType implements \Magento\Framework\Option\ArrayInterface
{
    function toOptionArray()
    {
        return [
            [
                'value' => 'AMEX',
                'label' => __('American Express'),
            ],
            [
                'value' => 'DISCOVER',
                'label' => __('Discover'),
            ],
            [
                'value' => 'JCB',
                'label' => __('JCB'),
            ],
            [
                'value' => 'MASTERCARD',
                'label' => __('MasterCard'),
            ],
            [
                'value' => 'VISA',
                'label' => __('Visa'),
            ]
        ];
    }
}
