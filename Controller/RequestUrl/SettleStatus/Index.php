<?php

namespace Securetrading\Trust\Controller\RequestUrl\SettleStatus;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use SecureTrading\Trust\Gateway\Config\Config;
use SecureTrading\Trust\Helper\Logger\Logger;
use SecureTrading\Trust\Helper\TransactionDetailHelper;

/**
 * Class Index
 * @package Securetrading\Trust\Controller\RequestUrl\SettleStatus
 */
class Index extends Action
{
    /**
     * @var TransactionDetailHelper
     */
    protected $helper;
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Index constructor.
     * @param TransactionDetailHelper $helper
     * @param Config $config
     * @param Context $context
     */
    public function __construct(Logger $logger,
								TransactionDetailHelper $helper,
								Config $config,
								Context $context)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @return bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try
        {
            if($jsonString = $this->getRequest()->getParams())
            {
                $data = json_decode($jsonString['data'],true);

                $reponse = $this->helper->getTransactionDetail($data,$this->config);

                return $reponse;
            }
        }
        catch (LocalizedException $e)
        {
            $this->logger->addDebug($e->getMessage());
        }

        return false;
    }
}