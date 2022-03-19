<?php


namespace SecureTrading\Trust\Gateway\Command\Api;


use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Gateway\Command\AbstractCommand;
use SecureTrading\Trust\Helper\Logger\Logger;

class GetLinkRedirectCommand implements CommandInterface
{
    protected $handler;

    protected $requestBuilder;

    protected $transferFactory;

    protected $logger;

    protected $validator;

    protected $errorMessageMapper;

    public function __construct(
        BuilderInterface $requestBuilder,
        HandlerInterface $handler,
        TransferFactoryInterface $transferFactory,
        Logger $logger,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    )
    {
        $this->handler = $handler;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->errorMessageMapper = $errorMessageMapper;
    }


    public function execute(array $commandSubject)
    {
        try {
            $data = $this->requestBuilder->build($commandSubject);
            $response = $this->transferFactory->create($data);
            $transactionDetailData = $response->getSingle('responses')->getSingle(0)->getAll();
            $this->logger->debug('--- RESPONSE GET LINK REDIRECT :', array($transactionDetailData));
            return $transactionDetailData;
        } catch (\Exception $e) {
            $this->logger->addDebug('API SecureTrading Error:' . $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
