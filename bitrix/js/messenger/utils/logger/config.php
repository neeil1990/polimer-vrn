<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/utils/logger/messenger.utils.logger.bundle.js',
	],
	'rel' => [
		'main.polyfill.complex',
	],
	'skip_core' => true,
];