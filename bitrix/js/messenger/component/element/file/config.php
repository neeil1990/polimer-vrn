<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/messenger/component/element/file/messenger.component.element.file.bundle.js',
	],
	'css' => [
		'/bitrix/js/messenger/component/element/file/messenger.component.element.file.bundle.css',
	],
	'rel' => [
		'main.polyfill.complex',
		'ui.vue',
		'messenger.model.files',
		'ui.vue.directives.lazyload',
		'ui.icons',
	],
	'skip_core' => true,
];