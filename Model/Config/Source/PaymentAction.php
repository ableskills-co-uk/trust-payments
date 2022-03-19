<?php

namespace SecureTrading\Trust\Model\Config\Source;

/**
 * Class PaymentAction
 *
 * @package SecureTrading\Trust\Model\Config\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'authorize', 'label' => 'Authorize Only'],
                ['value' => 'authorize_capture', 'label' => 'Authorize & Capture']];
    }
}