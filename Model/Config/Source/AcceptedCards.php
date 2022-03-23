<?php

namespace SecureTrading\Trust\Model\Config\Source;


class AcceptedCards implements \Magento\Framework\Option\ArrayInterface
{
    const CARD_AMEX = 'AMEX';
    const CARD_ASTROPAYCARD = 'ASTROPAYCARD';
    const CARD_COAST = 'COAST';
    const CARD_DELTA = 'DELTA';
    const CARD_DINERS = 'DINERS';
    const CARD_DISCOVER = 'DISCOVER';
    const CARD_ELECTRON = 'ELECTRON';
    const CARD_JCB = 'JCB';
    const CARD_KARENMILLEN = 'KARENMILLEN';
    const CARD_MAESTRO = 'MAESTRO';
    const CARD_MASTERCARD = 'MASTERCARD';
    const CARD_MASTERCARDDEBIT = 'MASTERCARDDEBIT';
    const CARD_OASIS = 'OASIS';
    const CARD_PIBA = 'PIBA';
    const CARD_PRINCIPLE = 'PRINCIPLE';
    const CARD_PURCHASING = 'PURCHASING';
    const CARD_SHOESTUDIO = 'SHOESTUDIO';
    const CARD_SOFORT = 'SOFORT';
    const CARD_SOLO = 'SOLO';
    const CARD_SWITCH = 'SWITCH';
    const CARD_VISA = 'VISA';
    const CARD_VPAY = 'VPAY';
    const CARD_WAREHOUSE = 'WAREHOUSE';
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
            self::CARD_AMEX             => 'American Express',
            self::CARD_ASTROPAYCARD     => 'Astropay',
            self::CARD_COAST            => 'Coast',
            self::CARD_DELTA            => 'Delta',
            self::CARD_DINERS           => 'Diners',
            self::CARD_DISCOVER         => 'Discover',
            self::CARD_ELECTRON         => 'Electron',
            self::CARD_JCB              => 'JCB',
            self::CARD_KARENMILLEN      => 'Karen Millen',
            self::CARD_MAESTRO          => 'Maestro',
            self::CARD_MASTERCARD       => 'Mastercard',
            self::CARD_MASTERCARDDEBIT  => 'Mastercard Debit',
            self::CARD_OASIS            => 'Oasis',
            self::CARD_PIBA             => 'PIBA',
            self::CARD_PRINCIPLE        => 'Principle',
            self::CARD_PURCHASING       => 'Purchasing',
            self::CARD_SHOESTUDIO       => 'Shoe Studio',
            self::CARD_SOFORT           => 'Sofort',
            self::CARD_SOLO             => 'Solo',
            self::CARD_SWITCH           => 'Switch',
            self::CARD_VISA             => 'Visa',
            self::CARD_VPAY             => 'VPay',
            self::CARD_WAREHOUSE        => 'Warehouse',
        );
    }
}
