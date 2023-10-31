<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/model/files/messenger.model.files.bundle.js',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue.vuex'
	],
	'skip_core' => true,
];