<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Bitrix\Main;

$arResult['HEADERS'] = [
	'INDEX',
	'ACCOUNT_NUMBER',
	'ORDER_ID',
	'DATE_SHIPMENT',
	'OFFER_ID',
	'OFFER_NAME',
	'QUANTITY',
];
$arResult['TABLE'] = [];
$tableNumber = 1;

foreach ($arResult['ITEMS'] as $order)
{
	$orderData = [
		'ACCOUNT_NUMBER' => $order['ACCOUNT_NUMBER'],
		'ORDER_ID' => $order['ID'],
		'DATE_SHIPMENT' => array_map(static function($date) {
			return $date instanceof Main\Type\Date ? Market\Data\Date::format($date) : (string)$date;
		}, $order['DATE_SHIPMENT']),
	];
	$isFirstOrderItem = true;

	foreach ($order['BASKET'] as $item)
	{
		$row = [
			'INDEX' => $tableNumber,
			'OFFER_ID' => $item['OFFER_ID'],
			'OFFER_NAME' => $item['OFFER_NAME'],
			'QUANTITY' => $item['QUANTITY'],
		];

		if ($isFirstOrderItem)
		{
			$row += $orderData;
		}

		$arResult['TABLE'][] = $row;

		$isFirstOrderItem = false;
		++$tableNumber;
	}
}