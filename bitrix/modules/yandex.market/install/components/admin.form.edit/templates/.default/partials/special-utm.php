<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$this->addExternalCss('/bitrix/css/yandex.market/ui/collapse.css');
$this->addExternalJs('/bitrix/js/yandex.market/ui/collapse.js');

$hasValue = false;

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);
	$fieldValue = $field ? trim($component->getFieldValue($field)) : '';

	if ($fieldValue !== '')
	{
		$hasValue = true;
		break;
	}
}

$langUtm = [
	'TOGGLE' => $hasValue
		? Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_UTM_TOGGLE_FILL')
		: Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_UTM_TOGGLE'),
	'TOGGLE_ALT' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_UTM_TOGGLE_ALT')
];

?>
<tr class="js-UTM_GROUP">
	<td width="40%" align="right" valign="top">&nbsp;</td>
	<td width="60%">
		<a class="js-plugin" href="#" data-plugin="Ui.Collapse" data-target-element="#ym-utm-group" data-alt="<?= $langUtm['TOGGLE_ALT'] ?>">
			<?= $langUtm['TOGGLE']; ?>
		</a>
	</td>
</tr>
<tr>
	<td colspan="2">
		<div class="b-collapse" id="ym-utm-group">
			<table width="100%">
				<?
				foreach ($specialFields as $specialFieldKey)
				{
					$field = $component->getField($specialFieldKey);

					if ($field)
					{
						?>
						<tr>
							<td width="40%" align="right" valign="top" class="adm-detail-content-cell-l"><?= $component->getFieldTitle($field); ?></td>
							<td width="60%" class="adm-detail-content-cell-r"><?= $component->getFieldHtml($field); ?></td>
						</tr>
						<?
					}
				}
				?>
			</table>
		</div>
	</td>
</tr>