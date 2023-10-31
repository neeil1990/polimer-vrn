<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/component/message/messenger.component.message.bundle.js',
	],
	'css' => [
		'/bitrix/js/messenger/component/message/messenger.component.message.bundle.css',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue',
		'ui.vue.vuex',
		'messenger.component.message.body',
		'messenger.model.dialogues',
		'messenger.model.messages',
		'messenger.model.users',
		'messenger.model.files',
	],
	'skip_core' => true,
];