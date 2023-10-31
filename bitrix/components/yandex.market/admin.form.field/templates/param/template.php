<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Yandex\Market\Ui\UserField\Helper\Attributes;
use Bitrix\Main\Localization\Loc;

if (!empty($arResult['ERRORS']))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => implode('<br />', $arResult['ERRORS']),
		'HTML' => true
	]);

	return;
}

Market\Ui\Assets::loadPlugins([
	'/js/lib.select2.select2',
	'/js/lib.select2.select2\\.theme',
], 'css');

Market\Ui\Assets::loadPlugins([
	'lib.select2.select2',
	'lib.select2.SearchAdapter',
	'Ui.Input.TagInput',
	'Ui.Input.Template',
	'Ui.Input.Formula',
	'Source.Manager',
	'Field.Param.Tag',
	'Field.Param.TagCollection',
	'Field.Param.Node',
	'Field.Param.NodeCollection',
]);

$lang = [
	'SELECT_PLACEHOLDER' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_SELECT_PLACEHOLDER'),
	'PARAM_SIZE_NAME' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_PARAM_SIZE_NAME'),
	'PARAM_SIZE_WARNINIG_REQUIRE_UNIT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_PARAM_SIZE_WARNINIG_REQUIRE_UNIT'),
];

$langStatic = [
	'WARNING' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_WARNING'),
	'HEADER_TAG' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_HEADER_TAG'),
	'HEADER_SOURCE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_HEADER_SOURCE'),
	'HEADER_FIELD' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_HEADER_FIELD'),
	'ADD_TAG' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_ADD_TAG'),
	'SETTINGS_UTM_TOGGLE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_SETTINGS_UTM_TOGGLE'),
	'SETTINGS_UTM_TOGGLE_FILL' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_SETTINGS_UTM_TOGGLE_FILL'),
	'SETTINGS_UTM_TOGGLE_ALT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_SETTINGS_UTM_TOGGLE_ALT'),
];

$fieldId = 'param-' . $this->randString(5);
$addTagList = [];

?>
<div class="b-param-table js-plugin js-param-manager" <?= Attributes::stringify([
	'id' => $fieldId,
	'data-plugin' => 'Field.Param.TagCollection',
	'data-base-name' => $arParams['INPUT_NAME'],
	'data-item-element' => '.js-param-tag-collection__item.level--0',
	'data-item-add-holder-element' => '.js-param-tag-collection__item-add-holder.level--0',
	'data-item-add-element' => '.js-param-tag-collection__item-add.level--0',
	'data-item-delete-element' => '.js-param-tag-collection__item-delete.level--0',
]) ?>>
	<table class="b-param-table__row">
		<tr>
			<td class="b-param-table__cell width--param-label">&nbsp;</td>
			<td class="b-param-table__cell" colspan="3">
				<?php
				echo BeginNote('style="max-width: 550px; margin-top: -16px;"');
				echo $langStatic['WARNING'];
				echo EndNote();
				?>
			</td>
		</tr>
	</table>
	<table class="b-param-table__row">
		<tr>
			<th class="b-param-table__cell for--label"><?= $langStatic['HEADER_TAG']; ?></th>
			<th class="b-param-table__cell width--param-source-cell"><?= $langStatic['HEADER_SOURCE']; ?></th>
			<th class="b-param-table__cell width--param-field-cell"><?= $langStatic['HEADER_FIELD']; ?></th>
			<th>&nbsp;</th>
		</tr>
	</table>
	<?php
	$tagIndex = 0;

	/** @var \Yandex\Market\Export\Xml\Tag\Base $tag */
	foreach ($arResult['TAGS'] as $tagId => $tag)
	{
		if (Market\Data\TextString::getPosition($tagId, '.') !== false) { continue; } // children

		$tagLevel = 0;
		$tagValues = [];
		$parentBaseId = '';
		$parentInputName = $arParams['INPUT_NAME'];
		$isParentPlaceholder = $arParams['PLACEHOLDER'];

		foreach ($arParams['VALUE'] as $rowValue)
		{
			if ($tagId === $rowValue['XML_TAG'])
			{
				$tagValues[] = $rowValue;
			}
		}

		if ($tag->isMultiple() || $tag->isUnion())
		{
			$addTagList[$tagId] = true;
		}
		else if (!$tag->isRequired() && !$tag->isVisible())
		{
			$addTagList[$tagId] = empty($tagValues);
		}

		include __DIR__ . '/partials/tag.php';
	}
	?>
	<div class="b-param-table__footer">
		<table class="b-param-table__row">
			<tr>
				<td class="b-param-table__cell width--param-label">&nbsp;</td>
				<td class="b-param-table__cell">
					<span class="js-params--show-hidden-tags js-param-tag-collection__item-add-holder level--0 <?= count(array_filter($addTagList)) > 0 ? '' : 'is--hidden'; ?>">
						<span class="adm-btn" tabindex="0"><?= $langStatic['ADD_TAG']; ?></span>
						<span class="js-params--hidden-tags">
							<?php
							foreach ($addTagList as $tagId => $isActive)
							{
								?>
								<span class="<?= $isActive ? '' : 'is--hidden'; ?> js-param-tag-collection__item-add level--0" tabindex="0" data-type="<?= $tagId; ?>"><?= htmlspecialcharsbx('<' . $tagId . '>'); ?></span>
								<?php
							}
							?>
						</span>
					</span>
					<?php
					if (!empty($arResult['DOCUMENTATION_LINK']))
					{
						?>
						<div class="b-admin-message-list spacing--1x2">
							<?php
							\CAdminMessage::ShowMessage([
								'TYPE' => 'OK',
								'MESSAGE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_DOCUMENTATION_TITLE'),
								'DETAILS' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_DOCUMENTATION_DETAILS', [
									'#URL#' => $arResult['DOCUMENTATION_LINK']
								]),
								'HTML' => true
							]);
							?>
						</div>
						<?php
					}

					if (!empty($arResult['DOCUMENTATION_BETA']))
					{
						?>
						<div class="b-admin-message-list">
							<?php
							\CAdminMessage::ShowMessage([
								'TYPE' => 'ERROR',
								'MESSAGE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_DOCUMENTATION_BETA', [
									'#FORMAT_NAME#' => $arParams['CONTEXT']['EXPORT_FORMAT']
								]),
								'HTML' => true
							]);
							?>
						</div>
						<?php
					}
					?>
				</td>
			</tr>
		</table>
	</div>
</div>
<?php
$managerData = [
	'types' => array_values($arResult['SOURCE_TYPE_ENUM']),
	'fields' => array_values($arResult['SOURCE_FIELD_ENUM']),
	'recommendation' => $arResult['RECOMMENDATION'],
	'typeMap' => $arResult['TYPE_MAP_JS']
];
?>
<script>
	(function() {
		var Source = BX.namespace('YandexMarket.Source');
		var utils = BX.namespace('YandexMarket.Utils');

		// init source manager

		new Source.Manager('#<?= $fieldId ?>', <?= Market\Utils::jsonEncode($managerData, JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR); ?>);

		// extend lang

		utils.registerLang(<?= Market\Utils::jsonEncode($lang, JSON_UNESCAPED_UNICODE); ?>, 'YANDEX_MARKET_FIELD_PARAM_');
	})();
</script>