<?
	define('NOT_CHECK_PERMISSIONS', true);
	define('NO_AGENT_CHECK', true);

	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
?>
<!DOCTYPE html>
<html>
	<head>
		<meta name="robots" content="noindex, nofollow, noarchive">
	</head>
	<body>
		<?php
		if (Bitrix\Main\Loader::includeModule('sale'));
		{
			$context = Bitrix\Main\Application::getInstance()->getContext();
			$request = $context->getRequest();
			$paymentNumber = $request->get('payment_number');

			$dbRes = \Bitrix\Sale\Payment::getList(array('select' => array('ORDER_ID', 'ID', 'SUM', 'DATE_BILL', 'CURRENCY'), 'filter' => array('ACCOUNT_NUMBER' => $paymentNumber)));
			if ($data = $dbRes->fetch())
			{
				$hash = md5($data['ID'].\Bitrix\Sale\Payment::roundByFormatCurrency($data['SUM'], $data['CURRENCY']).$data['DATE_BILL']);
				if ($hash == $request->get('hash'))
				{
					/** @var Bitrix\Sale\Order $order */
					$order = \Bitrix\Sale\Order::load($data['ORDER_ID']);
					if ($order)
					{
						/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
						$paymentCollection = $order->getPaymentCollection();
						if ($paymentCollection)
						{
							if ($data['ID'] > 0)
							{
								/** @var \Bitrix\Sale\Payment $payment */
								$payment = $paymentCollection->getItemById($data['ID']);
								$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
								if ($service)
								{
									$service->initiatePay($payment);
								}
							}
						}
					}
				}
			}
		}
		?>
	</body>
</html>