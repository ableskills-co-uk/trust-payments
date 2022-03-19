<?php

namespace Securetrading\Trust\Plugin\Order\Creditmemo\Create;

/**
 * Class Items
 * @package Securetrading\Trust\Plugin\Order\Creditmemo\Create
 */
class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    /**
     * @param $layout
     * @return mixed
     */
    public function afterSetLayout($layout)
    {
        if($this->getRequest()->getParam('invoice_id'))
        {
            $payment = $layout->getCreditmemo()->getInvoice()->getOrder()->getPayment();
            if ($payment->getMethod() == "secure_trading" && isset($payment->getAdditionalInformation()['multishipping_data']) && isset($payment->getAdditionalInformation()['multishipping_set_id']))
            {
                $submitButton = $layout->getChildBlock('submit_button');
                $submitButton->setData('onclick','');
            }
        }

        return $layout;
    }
}