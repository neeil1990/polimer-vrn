<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js' => Array(
		'/bitrix/js/messenger/utils/timer/messenger.utils.timer.bundle.js',
	),
	'rel' => [
		'main.polyfill.complex',
	],
	'skip_core' => true,
);