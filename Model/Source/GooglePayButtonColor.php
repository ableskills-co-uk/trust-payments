<?php


namespace SecureTrading\Trust\Model\Source;


class GooglePayButtonColor implements \Magento\Framework\Option\ArrayInterface
{
    function toOptionArray()
    {
        return [
            [
                'value' => 'black',
                'label' => __('Black'),
            ],
            [
                'value' => 'white',
                'label' => __('White'),
            ]
        ];
    }
}
