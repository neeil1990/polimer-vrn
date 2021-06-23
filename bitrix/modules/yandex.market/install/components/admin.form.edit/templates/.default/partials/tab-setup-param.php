<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $isActiveTab bool */

if (!empty($arResult['ITEM']['IBLOCK_LINK']))
{
	foreach ($arResult['ITEM']['IBLOCK_LINK'] as $iblockLinkIndex => $iblockLink)
	{
		$iblockLinkBaseName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_';

		$paramField = $component->getField($iblockLinkBaseName . 'PARAM');

		if (isset($paramField))
		{
			$iblock = (
				!empty($iblockLink['IBLOCK_ID']) && isset($arResult['IBLOCK_DATA'][$iblockLink['IBLOCK_ID']])
					? $arResult['IBLOCK_DATA'][$iblockLink['IBLOCK_ID']]
					: null
			);

			?>
			<tr class="heading">
				<td colspan="2">
					<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_IBLOCK_SECTION', [
						'#IBLOCK_NAME#' => $iblock !== null ? '&laquo;' . $iblock['NAME'] . '&raquo;' : '#' . $iblockLink['IBLOCK_ID']
					]); ?>
				</td>
			</tr>
			<?php
			include __DIR__ . '/warning.php';
			?>
			<tr>
				<td colspan="2">
					<?
					$APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'param', [
						'INPUT_NAME' => $paramField['FIELD_NAME'],
						'MULTIPLE' => 'Y',
						'VALUE' => $component->getFieldValue($paramField),
						'CONTEXT' => $iblockLink['CONTEXT'],
						'ACTIVE_TAB' => $isActiveTab
					]);
					?>
				</td>
			</tr>
			<?
		}
	}
}
