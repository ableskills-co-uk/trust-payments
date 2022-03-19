<?php

namespace SecureTrading\Trust\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class OrderCommand
 *
 * @package SecureTrading\Trust\Gateway\Command
 */
class OrderCommand implements CommandInterface
{
	/**
	 * @var BuilderInterface
	 */
	private $requestBuilder;

	/**
	 * @var HandlerInterface|null
	 */
	private $handler;

	/**
	 * OrderCommand constructor.
	 *
	 * @param BuilderInterface $requestBuilder
	 * @param HandlerInterface|null $handler
	 */
	public function __construct(
		BuilderInterface $requestBuilder,
		HandlerInterface $handler = null
	) {
		$this->handler        = $handler;
		$this->requestBuilder = $requestBuilder;

	}

	/**
	 * @param array $commandSubject
	 * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
	 */
	public function execute(array $commandSubject)
	{
		$response = $this->requestBuilder->build($commandSubject);
		if ($this->handler) {
			$this->handler->handle(
				$commandSubject,
				$response
			);
		}
	}
}