<?php

namespace SecureTrading\Trust\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use SecureTrading\Trust\Helper\Logger\Logger;

/**
 * Class AbstractResponseValidator
 *
 * @package SecureTrading\Trust\Gateway\Validator
 */
abstract class AbstractResponseValidator extends AbstractValidator
{
	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * AbstractResponseValidator constructor.
	 *
	 * @param ResultInterfaceFactory $resultFactory
	 * @param Logger $logger
	 */
	function __construct(
		ResultInterfaceFactory $resultFactory,
		Logger $logger
	) {
		$this->logger = $logger;
		parent::__construct($resultFactory);
	}
}