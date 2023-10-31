<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/component/message/body/messenger.component.message.body.bundle.js',
	],
	'css' => [
		'/bitrix/js/messenger/component/message/body/messenger.component.message.body.bundle.css',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue',
		'ui.vue.vuex',
		'messenger.component.element.file',
		'messenger.model.dialogues',
		'messenger.model.users',
		'messenger.model.files',
	],
	'skip_core' => true,
];