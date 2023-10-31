<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		?>
		<tr>
			<td class="adm-detail-content-cell-l" width="40%" align="right" valign="middle">
				<?
				include __DIR__ . '/field-title.php';
				?>
			</td>
			<td class="adm-detail-content-cell-r" width="60%">
				<?= $component->getFieldHtml($field); ?>
			</td>
		</tr>
		<?

		if ($field['EDIT_IN_LIST'] === 'N')
		{
			?>
			<tr>
				<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
				<td class="adm-detail-content-cell-r" width="60%">
					<input type="hidden" name="<?= $field['FIELD_NAME'] ?>" value="" />
					<div class="b-admin-message-list" style="margin-top: -16px;">
						<?
						\CAdminMessage::ShowMessage([
							'TYPE' => 'ERROR',
							'MESSAGE' => Main\Localization\Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_REFRESH_PERIOD_DISABLED_WARNING'),
							'DETAILS' => Main\Localization\Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_REFRESH_PERIOD_DISABLED_WARNING_DETAILS'),
							'HTML' => true
						]);
						?>
					</div>
				</td>
			</tr>
			<?
		}
	}
}