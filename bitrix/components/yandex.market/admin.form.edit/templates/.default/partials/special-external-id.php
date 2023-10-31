<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */

$specialNote = null;

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		if ($specialFieldKey === 'EXTERNAL_ID' && !empty($field['NOTE']))
		{
			$specialNote = $field['NOTE'];
			$field['NOTE'] = null;
		}

		include __DIR__ . '/field.php';
	}
}
?>
<tr>
	<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
	<td class="adm-detail-content-cell-r" width="60%">
		<div class="b-admin-message-list">
			<?php
			if ($specialNote !== null)
			{
				echo BeginNote();
				echo $specialNote;
				echo EndNote();
			}

			echo BeginNote();
			echo Main\Localization\Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_PRODUCT_COLLISION');
			echo EndNote();
			?>
		</div>
	</td>
</tr>