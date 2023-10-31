<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */

Loc::loadMessages(__FILE__);

$this->addExternalJs('/bitrix/js/yandex.market/ui/input/checktoggle.js');

$isExportAll = false;
$isFirstField = true;

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		$isExportAllField = ($specialFieldKey === 'SETUP_EXPORT_ALL');

		if ($isExportAllField)
		{
			$isExportAll = ($component->getFieldValue($field) === Market\Export\Promo\Table::BOOLEAN_Y);
		}

		if ($isExportAllField)
		{
			?>
			<tr class="js-plugin" data-plugin="Ui.Input.CheckToggle" data-input-element="input[type='checkbox']" data-target-element=".js-setup-link-target" data-inverse="true">
			<?
		}
		else
		{
			?>
			<tr class="<?= $isExportAll ? 'is--hidden' : ''; ?> js-setup-link-target">
			<?
		}
		?>
			<td width="40%" align="right" valign="top"><?
				if ($isFirstField)
				{
					echo '<strong>' . Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_SETUP_LINK_TITLE') . '</strong>';
				}
				else
				{
					echo '&nbsp;';
				}
			?></td>
			<td width="60%"><?
				echo $component->getFieldHtml($field);

				if ($isExportAllField)
				{
					echo '<label for="'.$field['FIELD_NAME'].'">' . Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_SETUP_LINK_FOR_ALL') . '</label>';
				}
			?></td>
		</tr>
		<?

		$isFirstField = false;
	}
}
