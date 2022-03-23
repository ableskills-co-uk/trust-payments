<?php

namespace SecureTrading\Trust\Model\Config\Source;

/**
 * Class Version
 *
 * @package SecureTrading\Trust\Model\Config\Source
 */
class Version implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    function toOptionArray()
    {
        return [['value' => 1, 'label' => 1], ['value' => 2, 'label' => 2]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    function toArray()
    {
        return [1 => 1, 2 => 2];
    }
}
