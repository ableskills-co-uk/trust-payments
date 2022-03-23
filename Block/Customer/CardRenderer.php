<?php

namespace SecureTrading\Trust\Block\Customer;

use SecureTrading\Trust\Model\Ui\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

/**
 * Class CardRenderer
 * @package SecureTrading\Trust\Block\Customer
 */
class CardRenderer extends AbstractCardRenderer
{
    /**
     * Can render specified token
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    function canRender(PaymentTokenInterface $token)
    {
        return ($token->getPaymentMethodCode() === ConfigProvider::CODE || $token->getPaymentMethodCode() === ConfigProvider::API_CODE);
    }

    /**
     * @return string
     */
    function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedpan'];
    }

    /**
     * @return string
     */
    function getExpDate()
    {
    	$token = $this->getToken();
    	if($token->getPaymentMethodCode() === ConfigProvider::API_CODE){
    		return $token->getExpiresAt();
	    }
        return $this->getTokenDetails()['cardExpire'][0][0]."/".$this->getTokenDetails()['cardExpire'][0][1];
    }

    /**
     * @return string
     */
    function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['paymentType'])['url'];
    }

    /**
     * @return int
     */
    function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['paymentType'])['height'];
    }

    /**
     * @return int
     */
    function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['paymentType'])['width'];
    }
}
