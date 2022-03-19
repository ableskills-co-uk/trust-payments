<?php

namespace SecureTrading\Trust\Block\Adminhtml\Subscription;

/**
 * Class Detail
 *
 * @package SecureTrading\Trust\Block\Adminhtml\Subscription
 */
class Detail extends \Magento\Backend\Block\Widget\Container
{
	/**
	 * Add control buttons
	 *
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$backUrl = $this->getUrl('securetrading/subscription/index');
		$this->buttonList->add(
			'back',
			['label' => __('Back'), 'onclick' => "setLocation('{$backUrl}')", 'class' => 'back']
		);
	}
}