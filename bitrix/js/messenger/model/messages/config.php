<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/model/messages/messenger.model.messages.bundle.js',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue.vuex'
	],
	'skip_core' => true,
];