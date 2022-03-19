<?php

namespace SecureTrading\Trust\Controller\PaymentPage;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Firebase\JWT\JWT;
use SecureTrading\Trust\Helper\Logger\Logger;


/**
 * Class Vault
 * @package SecureTrading\Trust\Controller\PaymentPage
 */
class Vault extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    protected $jsonFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var JWT
     */
    protected $jwt;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Start constructor.
     *
     * @param Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Controller\Result\Json $json
     */
    public function __construct(
        Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Controller\Result\Json $json,
        JWT $jwt,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory  = $json;
        $this->orderFactory = $orderFactory;
        $this->jwt = $jwt;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try
        {
            $orderId = $this->getRequest()->getParam('order_id');
            /** @var \Magento\Sales\Model\OrderFactory $order */
            $order = $this->orderFactory->create()->load(intval($orderId));
            $payment = $order->getPayment();
            $stData = $payment->getAdditionalInformation()['secure_trading_data'];

            $publicHash = base64_decode($payment->getAdditionalInformation()['public_hash']);
            $accountType = $payment->getAdditionalInformation()['account_type'];
            $baseAmount = !strpos($stData['mainamount'],'.')
                ? $stData['mainamount']
                : (int)str_replace('.', '', $stData['mainamount']);

            $currency = $stData['currencyiso3a'];
            $siteReference = $stData['sitereference'];
            $iat = time();
            $iss = $stData['jwt_name'];
            $settleStatus = $stData['settlestatus'];
            $secretkey = $stData['jwt_secret_key'];
            $orderrefference = $stData['orderreference'];

//      describe encode:  encode('payload','secretkey','Hash default is 256');
            $payload = [
                'payload' => [
                    "accounttypedescription" => $accountType,
                    "baseamount" => $baseAmount,
                    "currencyiso3a" => $currency,
                    "sitereference" => $siteReference,
                    "parenttransactionreference" => $publicHash,
                    "orderreference" => $orderrefference,
                    "settlestatus" => $settleStatus,
                    "credentialsonfile" => "2"
                ],
                "iat" => $iat,
                "iss" => $iss
            ];

			$data = [];

			if (is_array($subscriptionData = $this->processSubscriptionJWT($stData))) {
				$payload['payload'] = array_merge($payload['payload'], $subscriptionData);
				//Set flag for subscription
				$data['issubscription'] = 1;
				if (!empty($stData['skipthefirstpayment'])) {
					$data['skipthefirstpayment'] = 1;
				}

			}
            /** @var \Firebase\JWT\JWT $jwt */
            # 2022-03-18 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
            $jwt = $this->jwt->encode($payload, $secretkey, 'HS256');
            $this->logger->addDebug('--- JWT : ' . $jwt);
			$data['jwt'] = $jwt;
            $this->jsonFactory->setData($data);

            return $this->jsonFactory;
        }
        catch (\Exception $e)
        {
            $this->logger->addDebug('Vault Payment fail, Error:' . $e->getMessage());
            $this->messageManager->addErrorMessage("Something went wrong");
        }
    }

    public function processSubscriptionJWT(array $data)
	{
		if(!empty($data['issubscription']))
		{
			$subscriptionData = [
						"subscriptionunit" => $data["subscriptionunit"],
						"subscriptionfrequency" => (int)$data["subscriptionfrequency"],
						"subscriptionfinalnumber" => (int)$data["subscriptionfinalnumber"],
						"subscriptiontype" => $data["subscriptiontype"],
						"subscriptionnumber" => 1,
						"credentialsonfile" => 1,
					];
			if(!empty($data['skipthefirstpayment'])){
				$subscriptionData['skipthefirstpayment'] = 1;
			}

			return $subscriptionData;
		}
		return null;
	}
}
