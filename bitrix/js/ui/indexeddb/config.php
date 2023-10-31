<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/ui/indexeddb/dexie.js',
		'/bitrix/js/ui/indexeddb/ui.indexeddb.bundle.js',
	],
	'skip_core' => true,
	'rel' => [
		'main.polyfill.complex',
	],
];