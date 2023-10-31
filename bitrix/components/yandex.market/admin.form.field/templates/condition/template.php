<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

// assets

CJSCore::Init([
	'popup',
	'date',
]);

Market\Ui\Assets::loadPlugins([
	'/js/lib.select2.select2',
	'/js/lib.select2.select2\\.theme',
], 'css');

Market\Ui\Assets::loadPlugins([
	'lib.select2.select2',
	'lib.editdialog',
	'Ui.Input.TagInput',
	'Ui.Input.FilterInput',
	'Ui.Input.FilterDate',
	'Field.Condition.Summary',
	'Field.Condition.Collection',
	'Field.Condition.Item',
]);

$lang = [
	'MODAL_TITLE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_MODAL_TITLE'),
	'PLACEHOLDER' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_SUMMARY_PLACEHOLDER'),
	'JUNCTION' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_SUMMARY_JUNCTION'),
	'COUNT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_COUNT'),
	'COUNT_PROGRESS' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_COUNT_PROGRESS'),
	'COUNT_FAIL' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_COUNT_FAIL'),
	'COUNT_EMPTY' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_COUNT_EMPTY'),
	'PRODUCT_1' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PRODUCT_1'),
	'PRODUCT_2' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PRODUCT_2'),
	'PRODUCT_5' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PRODUCT_5'),
];
$langStatic = [
	'EDIT_BUTTON' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_SUMMARY_EDIT'),
	'FIELD_FIELD' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_FIELD_FIELD'),
	'PLACEHOLDER_FIELD' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_FIELD'),
	'FIELD_COMPARE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_FIELD_COMPARE'),
	'PLACEHOLDER_COMPARE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_COMPARE'),
	'FIELD_VALUE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_FIELD_VALUE'),
	'PLACEHOLDER_VALUE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_VALUE'),
	'NO_VALUE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_NO_VALUE'),
	'LOAD_PROGRESS' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_LOAD_PROGRESS'),
	'LOAD_ERROR' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_LOAD_ERROR'),
	'SEARCHING' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_SEARCHING'),
	'VALUE_TOO_LONG' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_VALUE_TOO_LONG'),
	'VALUE_TOO_SHORT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_VALUE_TOO_SHORT'),
	'VALUE_MAX_SELECT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_VALUE_MAX_SELECT'),
	'ADD_BUTTON' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_CONDITION_ADD_BUTTON'),
];
$langChosen = [
	'CHOSEN_PLACEHOLDER' => $langStatic['PLACEHOLDER_VALUE'],
	'CHOSEN_NO_RESULTS' => $langStatic['NO_VALUE'],
	'CHOSEN_LOAD_PROGRESS' => $langStatic['LOAD_PROGRESS'],
	'CHOSEN_LOAD_ERROR' => $langStatic['LOAD_ERROR'],
	'CHOSEN_SEARCHING' => $langStatic['SEARCHING'],
	'CHOSEN_TOO_LONG' => $langStatic['VALUE_TOO_LONG'],
	'CHOSEN_TOO_SHORT' => $langStatic['VALUE_TOO_SHORT'],
	'CHOSEN_MAX_SELECT' => $langStatic['VALUE_MAX_SELECT'],
];

// output

?>
<div class="<?= $arParams['CHILD'] ? $arParams['CHILD_CLASS_NAME'] : 'js-plugin'; ?>" data-plugin="Field.Condition.Summary" <?= $arParams['CHILD'] ? '' : 'data-base-name="' . $arParams['INPUT_NAME'] .'"';  ?> data-name="FILTER_CONDITION">
	<?
	include __DIR__ . '/partials/summary.php';
	?>
	<div class="is--hidden js-condition-summary__edit-modal">
		<?
		include __DIR__ . '/partials/table.php';
		?>
	</div>
</div>
<?
if ($APPLICATION->GetPageProperty('YANDEX_MARKET_FORM_FIELD_CONDITION_LANG') !== 'Y')
{
	$APPLICATION->SetPageProperty('YANDEX_MARKET_FORM_FIELD_CONDITION_LANG', 'Y');

	?>
	<script>
		(function() {
			var utils = BX.namespace('YandexMarket.Utils');

			utils.registerLang(<?= Market\Utils::jsonEncode($lang, JSON_UNESCAPED_UNICODE); ?>, 'YANDEX_MARKET_FIELD_CONDITION_'); // field lang
			utils.registerLang(<?= Market\Utils::jsonEncode($langChosen, JSON_UNESCAPED_UNICODE); ?>, 'YANDEX_MARKET_'); // chosen lang
		})();
	</script>
	<?
}