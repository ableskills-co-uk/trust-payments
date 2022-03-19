<?php

namespace SecureTrading\Trust\Helper;

use \Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class TransactionDetailHelper
 * @package Securetrading\Trust\Helper
 */
class TransactionDetailHelper
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * TransactionDetailHelper constructor.
     * @param JsonFactory $jsonFactory
     */
    public function __construct(JsonFactory $jsonFactory)
    {
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @param $dataPayment
     * @param $config
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function getTransactionDetail($dataPayment, $config)
    {
        $config->setMethodCode('secure_trading');

        $configData = array(
            'username' => $config->getValue('username'),
            'password' => $config->getValue('password')
        );
        $requestData = [
            'requesttypedescriptions' => array('TRANSACTIONQUERY'),
            'filter' => [
                'sitereference' => ['value' => $dataPayment['sitereference']],
                'currencyiso3a' => ['value' => $dataPayment['currencyiso3a']],
                'transactionreference' => ['value' => $dataPayment['transactionreference']],
            ]
        ];

        $api = \Securetrading\api($configData);
        $response = $api->process($requestData);
        $response = $this->jsonFactory->create()->setData($response->toArray());
        return $response;
    }
}