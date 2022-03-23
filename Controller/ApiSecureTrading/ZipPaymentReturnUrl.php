<?php


namespace SecureTrading\Trust\Controller\ApiSecureTrading;


use Magento\Framework\App\Action\Action;
use SecureTrading\Trust\Helper\Data;

class ZipPaymentReturnUrl extends Action
{
    protected $config;
    protected $checkoutSession;
    protected $orderRepository;
    protected $transactionUpdateTransferFactory;
    protected $logger;
    protected $authorizeAndCaptureCommand;
    protected $orderSender;

    function __construct(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \SecureTrading\Trust\Gateway\Command\AuthorizeAndCaptureCommand $authorizeAndCaptureCommand,
        \SecureTrading\Trust\Helper\Logger\Logger $logger,
        \SecureTrading\Trust\Gateway\Http\TransactionUpdateTransferFactory $transactionUpdateTransferFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \SecureTrading\Trust\Gateway\Config\Config $config,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->transactionUpdateTransferFactory = $transactionUpdateTransferFactory;
        $this->logger = $logger;
        $this->authorizeAndCaptureCommand = $authorizeAndCaptureCommand;
        $this->orderSender = $orderSender;
    }

    function execute()
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($this->checkoutSession->getLastRealOrder()->getId());
            $response = $this->getTransactionData($order);
            $this->logger->debug('ZIP query transaction '.json_encode($response));
            if (isset($response['responses'][0]['errorcode']) && $response['responses'][0]['errorcode'] == '0') {
                $record = $response['responses'][0]['records'][0] ?? [];
                if ($record && $record['orderreference'] == $order->getIncrementId() && $record['errorcode'] == '0') {
                    if ($record['settlestatus'] == '3') {
                        $order->cancel();
                        $this->orderRepository->save($order);
                        $this->messageManager->addErrorMessage(__('Your order has been canceled.'));
                        return $this->_redirect('checkout/cart');
                    }
                    $payment = $order->getPayment();
                    foreach ($record as $key => $param) {
                        $payment->setAdditionalInformation($key, $param);
                    }
                    $this->authorizeAndCaptureCommand->execute(['order' => $order, 'info' => $record]);
                    $this->orderSender->send($order);
                    $this->messageManager->addSuccessMessage(__('Your order has been successful.'));
                    return $this->_redirect('checkout/onepage/success');
                } else {
                    throw new \Exception('Could not fould transaction record.');
                }
            } else {
                throw new \Exception('Query transaction fail '. $response['responses'][0]['errormessage'] ?? '');
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Something went wrong %1', $exception->getMessage()));
        }
        return $this->_redirect('checkout/cart');
    }

    private function getTransactionData(\Magento\Sales\Model\Order $order) {
        $this->config->setMethodCode('api_secure_trading');
        $data = [
            'configData' => [
                'username' => $this->config->getValue(Data::USER_NAME, $order->getStoreId()),
                'password' => $this->config->getValue(Data::PASSWORD, $order->getStoreId()),
            ],
            'requestData' => [
                'requesttypedescriptions' => ['TRANSACTIONQUERY'],
                'filter' => [
                    'sitereference' => ['value' => $this->config->getValue(Data::SITE_REFERENCE, $order->getStoreId()),],
                    'currencyiso3a' => ['value' => $order->getBaseCurrencyCode()],
                    'transactionreference' => ['value' => $this->getRequest()->getParam('transactionreference')],
                ]
            ]
        ];
        $response = $this->transactionUpdateTransferFactory->create($data)->toArray();
        return $response;
    }
}
