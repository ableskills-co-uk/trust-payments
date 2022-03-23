<?php


namespace SecureTrading\Trust\Model\Source;


class GooglePayButtonType implements \Magento\Framework\Option\ArrayInterface
{
    function toOptionArray()
    {
        return [
            [
                'value' => 'buy',
                'label' => __('Buy'),
            ],
            [
                'value' => 'donate',
                'label' => __('Donate'),
            ],
            [
                'value' => 'plain',
                'label' => __('Plain'),
            ],
            [
                'value' => 'long',
                'label' => __('Long'),
            ],
            [
                'value' => 'short',
                'label' => __('Short'),
            ]
        ];
    }
}
