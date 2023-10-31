<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/utils/messenger.utils.bundle.js',
	],
	'rel' => [
		'main.polyfill.complex',
	],
	'skip_core' => true,
];