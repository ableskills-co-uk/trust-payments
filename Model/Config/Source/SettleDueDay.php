<?php

namespace SecureTrading\Trust\Model\Config\Source;

/**
 * Class SettleDueDay
 *
 * @package SecureTrading\Trust\Model\Config\Source
 */
class SettleDueDay implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    function toOptionArray()
    {
        $result = [];
        if(!empty($this->toArray())){
            foreach ($this->toArray() as $key=>$value)
            {
                $result[] = ['value'=>$key,'label'=>$value];
            }
        }
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    function toArray()
    {
        return array(
            0 => 'Process immediately',
            1 => 'Wait 1 day',
            2 => 'Wait 2 days',
            3 => 'Wait 3 days',
            4 => 'Wait 4 days',
            5 => 'Wait 5 days',
            6 => 'Wait 6 days',
            7 => 'Wait 7 days',
        );
    }
}
