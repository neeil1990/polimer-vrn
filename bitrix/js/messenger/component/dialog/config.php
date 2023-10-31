<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/component/dialog/messenger.component.dialog.bundle.js',
	],
	'css' => [
		'/bitrix/js/messenger/component/dialog/messenger.component.dialog.bundle.css',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue',
		'ui.vue.vuex',
		'main.polyfill.intersectionobserver',
		'ui.vue.directives.lazyload',
		'messenger.component.message',
		'messenger.model.dialogues',
		'messenger.model.messages',
	],
	'skip_core' => true,
];