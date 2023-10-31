<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/component/textarea/messenger.component.textarea.bundle.js',
	],
	'css' => [
		'/bitrix/js/messenger/component/textarea/messenger.component.textarea.bundle.css',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue',
		'messenger.utils',
		'messenger.utils.localstorage',
	],
	'skip_core' => true,
];