<?php

namespace SecureTrading\Trust\Plugin\Model\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;
use SecureTrading\Trust\Model\Ui\ConfigProvider;

/**
 * Class ReCalculateTotalPaid
 *
 * @package SecureTrading\Trust\Plugin\Model\Order\Invoice
 */
class ReCalculateTotalPaid
{
	/**
	 * @param Invoice $subject
	 * @param callable $proceed
	 * @return mixed
	 */
	public function aroundPay(Invoice $subject, callable $proceed)
	{
		$order   = $subject->getOrder();
		$payment = $order->getPayment();
		$method  = $payment->getMethod();
		if ($method == ConfigProvider::CODE || $method == ConfigProvider::VAULT_CODE) {
			$stData = $payment->getAdditionalInformation('secure_trading_data');
			if (!empty($stData['issubscription'])) {
				if ($stData['subscriptiontype'] == 'INSTALLMENT') {
					$totalPaid     = $subject->getGrandTotal();
					$baseTotalPaid = $subject->getBaseGrandTotal();
					$invoiceList   = $order->getInvoiceCollection();
					// calculate all totals
					if (count($invoiceList->getItems()) == 1) {
						$totalPaid     += $order->getTotalPaid();
						$baseTotalPaid += $order->getBaseTotalPaid();
						$result        = $proceed();
						$order->setTotalPaid($totalPaid);
						$order->setBaseTotalPaid($baseTotalPaid);
						return $result;
					}
				}
			}
		}
		return $proceed();
	}
}