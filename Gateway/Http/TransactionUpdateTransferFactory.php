<?php

namespace SecureTrading\Trust\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferFactoryInterface;

/**
 * Class TransactionUpdateTransferFactory
 * @package SecureTrading\Trust\Gateway\Http
 */
class TransactionUpdateTransferFactory implements TransferFactoryInterface
{
    /**
     * @param array $request
     * @return \Magento\Payment\Gateway\Http\TransferInterface
     */
    function create(array $request)
    {
        $api = \Securetrading\api($request['configData']);
        return  $api->process($request['requestData']);
    }
}
