<?
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global array $arParams
 * @global array $arResult
 * @global string $templateFolder
 * @global CBitrixComponentTemplate $this
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;

$styles = [
	'/bitrix/components/bitrix/main.lookup.input/templates/iblockedit/style.css',
];
$scripts = [
	$this->__component->GetPath() . '/script.js',
	'/bitrix/components/bitrix/main.lookup.input/templates/iblockedit/script2.js',
	$templateFolder . '/script2.js',
];

foreach ($styles as $style)
{
	$APPLICATION->SetAdditionalCSS($style);
}

foreach ($scripts as $script)
{
	$APPLICATION->AddHeadScript($script);
}

$control_id = $arParams['CONTROL_ID'];
$textarea_id = (!empty($arParams['INPUT_NAME_STRING']) ? $arParams['INPUT_NAME_STRING'] : 'visual_'.$control_id);
$boolStringValue = (isset($arParams['INPUT_VALUE_STRING']) && (string)$arParams['INPUT_VALUE_STRING'] !== '');
$useSiblingInit = (isset($arParams['SIBLINGS_INIT']) && $arParams['SIBLINGS_INIT'] === 'Y');
$siblingClassName = null;
$INPUT_VALUE = array();

$mliLayoutClass = '';
$mliFieldClass = '';

if ($arParams['MAIN_UI_FILTER'] === 'Y')
{
	$mliLayoutClass = 'mli-layout-ui-filter';
	$mliFieldClass = 'mli-field-ui-filter';
}

if ($boolStringValue)
{
	$arTokens = preg_split('/(?<=])[\n;,]+/', $arParams['~INPUT_VALUE_STRING']);
	foreach($arTokens as $key => $token)
	{
		if(preg_match("/^(.*) \\[(\\d+)\\]/", $token, $match))
		{
			$match[2] = intval($match[2]);
			if (0 < $match[2])
				$INPUT_VALUE[] = array(
					"ID" => $match[2],
					"NAME" => $match[1],
				);
		}
	}
}

$commonParameters = array(
	'AJAX_PAGE' => $this->GetFolder()."/ajax.php",
	'AJAX_PARAMS' => [
		'lang' => LANGUAGE_ID,
		'site' => SITE_ID,
		'provider' => $arParams['PROVIDER'],
	],
	'PROACTIVE' => 'MESSAGE',
	'VISUAL' => array(
		'MAIN_UI_FILTER' => $arParams['MAIN_UI_FILTER'],
		'MULTIPLE' => $arParams['MULTIPLE'],
		'START_TEXT' => $arParams['START_TEXT'],
		'SEARCH_POSITION' => ($arParams['FILTER'] === 'Y' ? 'absolute' : ''),
		'SEARCH_ZINDEX' => 4000,
	),
);
$selfParameters = array(
	'CONTROL_ID' => $control_id,
	'LAYOUT_ID' => 'layout_'.$control_id,
	'INPUT_NAME' => $arParams['~INPUT_NAME'],
	'VALUE' => $INPUT_VALUE,
	'VISUAL' => array(
		'ID' => $textarea_id,
	),
);

$allParameters = array_merge_recursive($commonParameters, $selfParameters);

if ($useSiblingInit)
{
	$siblingClassName = 'ym-autocomplete-group-' . md5(serialize($commonParameters));
	$mliLayoutClass .= ' ' . $siblingClassName;
}

?><div class="mli-layout <?= $mliLayoutClass ?>" id="layout_<?= $control_id ?>">
	<div style="display:none" id="value_container_<?=$control_id?>"><?php
		if ($INPUT_VALUE)
		{
			foreach ($INPUT_VALUE as $value)
			{
				echo sprintf('<input type="hidden" name="%s" value="%s">', $arParams['~INPUT_NAME'], $value['ID']);
			}
		}
		else
		{
			echo sprintf('<input type="hidden" name="%s" value="">', $arParams['~INPUT_NAME']);
		}
	?></div><?php

	if ($arParams["MULTIPLE"]==="Y" && $arParams['MAIN_UI_FILTER'] !== 'Y')
	{
		?><textarea
			class="mli-field"
			name="<?= $textarea_id ?>"
			id="<?= $textarea_id ?>"
		><?= ($boolStringValue ? htmlspecialcharsbx($arParams['INPUT_VALUE_STRING']) : '') ?></textarea><?php
	}
	else
	{
		?><input
			class="mli-field <?= $mliFieldClass ?>"
			type="text"
			name="<?= $textarea_id ?>"
			id="<?= $textarea_id ?>"
			value="<?= ($boolStringValue ? htmlspecialcharsbx($arParams['INPUT_VALUE_STRING']) : '') ?>"
			autocomplete="off"
		/><?php
	}

?></div>
<script type="text/javascript">
(function() {
	var BX = (top.BX || window.BX);

	BX.ready(function() {
		setTimeout(initialize, 500);
	});

	function initialize() {
		if (getPlugin() != null) {
			buildPlugin();
		} else if (!isLoadStarted()) {
			load(buildPlugin);
		}
	}

	function load(callback) {
		var styles = <?= Main\Web\Json::encode($styles) ?>;
		var scripts = <?= Main\Web\Json::encode($scripts) ?>;

		BX.loadCSS(styles);
		BX.loadScript(scripts, callback);
		markLoadStarted();
	}

	function isLoadStarted() {
		var components = BX.namespace('YandexMarket.Components');

		return components.loadAutocompleteLookup != null;
	}

	function markLoadStarted() {
		var components = BX.namespace('YandexMarket.Components');

		components.loadAutocompleteLookup  = true;
	}

	function getPlugin() {
		var components = BX.namespace('YandexMarket.Components');

		return components.AutocompleteLookup;
	}

	function buildPlugin() {
		<?php
		if ($useSiblingInit)
		{
			?>
			var AutocompleteLookup = getPlugin();
			var groupClassName = '<?= $siblingClassName; ?>';
			var siblings = document.getElementsByClassName(groupClassName);
			var siblingIndex;
			var sibling;
			var valueInput;
			var displayInput;
			var commonParameters = <?= CUtil::PhpToJSObject($commonParameters); ?>;
			var selfParameters;

			for (siblingIndex = 0; siblingIndex < siblings.length; siblingIndex++) {
				sibling = siblings[siblingIndex];
				valueInput = sibling.querySelector('input[type="hidden"]');
				displayInput = sibling.querySelector('input[type="text"], textarea');

				selfParameters = {
					'CONTROL_ID': sibling.id.replace('layout_', ''),
					'LAYOUT_ID': sibling.id,
					'INPUT_NAME': valueInput.name,
					'VISUAL': {
						'ID': displayInput.id
					}
				};

				new AutocompleteLookup(BX.merge(selfParameters, commonParameters));

				sibling.classList.remove(groupClassName);
			}
			<?php
		}
		else
		{
			?>
			var AutocompleteLookup = getPlugin();
			var autocomplete = new AutocompleteLookup(<?= CUtil::PhpToJSObject($allParameters); ?>);

			<?php
			if (array_key_exists('RESET', $arParams) && 'Y' === $arParams['RESET'])
			{
				?>autocomplete.Reset(true, false);<?php
			}
		}
		?>
	}
})();
</script>