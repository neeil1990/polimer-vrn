<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$chainMap = [
	'WARNINGS' => 'warning'
];
$messages = [];

foreach ($chainMap as $chainKey => $messageType)
{
	if (empty($arResult[$chainKey])) { continue; }

	foreach ($arResult[$chainKey] as $message)
	{
		$messages[] = [
			'type' => $messageType,
			'text' => $message,
		];
	}
}

$arResult['COLUMNS']['NOTIFICATION']['elements'][] = [
	'name' => 'NOTIFICATION_SECTION',
	'title' => '',
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
		'enableTitle' => false,
	],
	'elements' => [
		[ 'name' => 'NOTIFICATION' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'NOTIFICATION',
	'title' => '',
	'type' => 'yamarket_notification',
	'editable' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['NOTIFICATION'] = $messages;