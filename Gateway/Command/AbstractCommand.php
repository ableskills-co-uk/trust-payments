<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use SecureTrading\Trust\Helper\Data;
use Magento\Payment\Gateway\CommandInterface;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class AbstractCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
abstract class AbstractCommand implements CommandInterface
{
    /**==
     * @var BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    protected $transferFactory;

    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ErrorMessageMapperInterface
     */
    protected $errorMessageMapper;

    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

	/**
	 * @var Registry
	 */
	protected $coreRegistry;

    /**
     * CaptureCommand constructor.
	 * @param Registry $coreRegistry
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param Logger $logger
     * @param HandlerInterface $handler
     * @param ValidatorInterface $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     * @param ConfigInterface $config
     * @param CommandPoolInterface $commandPool
     */
    function __construct(
		Registry $coreRegistry,
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        Logger $logger,
        ConfigInterface $config,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->errorMessageMapper = $errorMessageMapper;
        $this->config = $config;
        $this->coreRegistry = $coreRegistry;
    }
    /**
     * Tries to map error messages from validation result and logs processed message.
     * Throws an exception with mapped message or default error.
     *
     * @param ResultInterface $result
     * @throws CommandException
     */
    protected function processErrors(ResultInterface $result)
    {
        $messages = [];
        $errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());
        foreach ($errorsSource as $errorCodeOrMessage) {
            $errorCodeOrMessage = (string) $errorCodeOrMessage;

            // error messages mapper can be not configured if payment method doesn't have custom error messages.
            if ($this->errorMessageMapper !== null) {
                $mapped = (string) $this->errorMessageMapper->getMessage($errorCodeOrMessage);
                if (!empty($mapped)) {
                    $messages[] = $mapped;
                    $errorCodeOrMessage = $mapped;
                }
            }
            $this->logger->debug('Payment Error: ' . $errorCodeOrMessage);
        }

        throw new CommandException(
            !empty($messages)
                ? __(implode(PHP_EOL, $messages))
                : __('Transaction has been declined. Please try again later.')
        );
    }
}