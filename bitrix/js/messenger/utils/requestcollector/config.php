<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/utils/requestcollector/messenger.utils.requestcollector.bundle.js',
	],
	'rel' => [
		'main.polyfill.complex',
	],
	'skip_core' => true,
];