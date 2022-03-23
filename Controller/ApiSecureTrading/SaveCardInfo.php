<?php


namespace SecureTrading\Trust\Controller\ApiSecureTrading;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use SecureTrading\Trust\Controller\ApiSecureTrading\GenerateJwt;

use Firebase\JWT\JWT;

/**
 * Class SaveCardInfo
 * @package SecureTrading\Trust\Controller\ApiSecureTrading
 */
class SaveCardInfo extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonResult;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var JWT
     */
    protected $jwt;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EncryptorInterface
     */
    protected $enc;

    /**
     * SaveCardInfo constructor.
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResult
     * @param \Magento\Framework\App\Action\Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $enc
     * @param JWT $jwt
     */
    function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Magento\Framework\App\Action\Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $enc,
        JWT $jwt
    )
    {
        $this->_jsonResult = $jsonResult;
        $this->_orderRepository = $orderRepository;
        $this->jwt = $jwt;
        $this->storeManager = $storeManager;
        $this->enc = $enc;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    function execute()
    {
        $isSave =  $this->_request->getParam('is_save');
        $orderId = $this->_request->getParam('order_id');
        if ($orderId ) {
            $isTest = $this->storeManager->getStore()->getConfig('payment/api_secure_trading/is_test');
            $secretkey = $this->enc->decrypt($isTest ? $this->storeManager->getStore()->getConfig(GenerateJwt::TEST_JWT_SECRET_KEY) : $this->storeManager->getStore()->getConfig(GenerateJwt::JWT_SECRET_KEY));
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_orderRepository->get($orderId);
            if ($order) {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $payment->setAdditionalInformation('save_card_info_api', $isSave);
                $jwt = $this->handleJwt($payment, $secretkey, $isSave);
                $payment->save();
            }
        }
        $result = $this->_jsonResult->create();
        $result->setData(['jwt'=> $jwt]);
        return $result;
    }

    /**
     * @param $payment
     * @param $secretkey
     * @param $isSave
     * @return string
     */
    function handleJwt($payment, $secretkey, $isSave){
        $jwt = $payment->getAdditionalInformation('jwt');
        # 2022-03-18 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
        $jwtDecode = (array)$this->jwt->decode($jwt, new \Firebase\JWT\Key($secretkey, 'HS256'));
        $payload = (array)$jwtDecode['payload'];
        $isSave ? $payload['credentialsonfile'] = 1 : $payload['credentialsonfile'] = 0;
        $jwtDecode['payload'] = $payload;
        # 2022-03-18 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
        $jwt = $this->jwt->encode($jwtDecode, $secretkey, 'HS256');
        $payment->setAdditionalInformation('jwt', $jwt);
        return $jwt;
    }
}
