<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $tab array */
/** @var $fields array */
/** @var $component Yandex\Market\Components\AdminFormEdit */

include __DIR__ . '/warning.php';

$fieldActiveGroup = null;

foreach ($fields as $fieldKey)
{
	if (!isset($arResult['SPECIAL_FIELDS_MAP'][$fieldKey]))
	{
		$field = $component->getField($fieldKey);

		if (isset($field['GROUP']) && $field['GROUP'] !== $fieldActiveGroup)
		{
			$fieldActiveGroup = $field['GROUP'];

			?>
			<tr class="heading">
				<td colspan="2"><?= $field['GROUP']; ?></td>
			</tr>
			<?php
			if (isset($field['GROUP_DESCRIPTION']))
			{
				?>
				<tr>
					<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
					<td class="adm-detail-content-cell-r" width="60%"><?= $field['GROUP_DESCRIPTION']; ?></td>
				</tr>
				<?php
			}
		}

		include __DIR__ . '/field.php';
	}
	else
	{
		$specialKey = $arResult['SPECIAL_FIELDS_MAP'][$fieldKey];

		if (!isset($arResult['SPECIAL_FIELDS_SHOWN'][$specialKey]))
		{
			$arResult['SPECIAL_FIELDS_SHOWN'][$specialKey] = true;
			$specialFields = $arResult['SPECIAL_FIELDS'][$specialKey];

			require __DIR__ . '/special-' . $specialKey . '.php';
		}
	}
}

if (isset($tab['DATA']['NOTE']))
{
	?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r" width="60%">
			<?php
			\CAdminMessage::ShowMessage([
				'TYPE' => 'OK',
				'MESSAGE' => $tab['DATA']['NOTE'],
				'DETAILS' => isset($tab['DATA']['NOTE_DESCRIPTION']) ? $tab['DATA']['NOTE_DESCRIPTION'] : null,
				'HTML' => true
			]);
			?>
		</td>
	</tr>
	<?php
}