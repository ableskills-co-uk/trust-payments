<?php

/**
 * This file is part of the Klarna KP module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace SecureTrading\Trust\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;


/**
 * Class ModuleVersion
 * @package SecureTrading\Trust\Block\Adminhtml\System\Config\Form\Field
 */
class ModuleVersion extends Field
{
    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    private $fullModuleList;

    /**
     * ModuleVersion constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Module\FullModuleList $fullModuleList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->fullModuleList = $fullModuleList;
    }

	/**
	 * @param AbstractElement $element
	 * @return string
	 */
	public function render(AbstractElement $element)
    {
        $moduleVersion = $this->fullModuleList->getOne('SecureTrading_Trust');
        $text = "<b>Module Version: </b>".$moduleVersion['setup_version']."</br><p>The fast and secure way to accept e-commerce payments from cards, mobile wallets and more. Our customisation options enable you to deploy a seamless checkout experience for your customers.</p>";
        return $text;
    }
}
