<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

/** @var $field array */

$rowAttributes = [];
$fieldControl = $component->getFieldHtml($field, null, true);
$fieldValign = $fieldControl !== null && $fieldControl['VALIGN'] ? $fieldControl['VALIGN'] : 'middle';
$fieldPushTitle = null;
$hasDescription = isset($field['DESCRIPTION']);
$hasNote = isset($field['NOTE']);
$hasAdditionalRow = ($hasDescription || $hasNote);

if (!empty($field['SETTINGS']['VALIGN']))
{
	$fieldValign = $field['SETTINGS']['VALIGN'];
}

if ($fieldValign === 'top')
{
	if (!empty($field['SETTINGS']['VALIGN_PUSH']))
	{
		$fieldPushTitle = $field['SETTINGS']['VALIGN_PUSH'] === true ? 'top' : $field['SETTINGS']['VALIGN_PUSH'];
	}
	else if ($field['CONTROL'] !== null)
	{
		$controlCount = (
			mb_substr_count($field['CONTROL'], ' type="text"')
			+ mb_substr_count($field['CONTROL'], ' type="number"')
			+ mb_substr_count($field['CONTROL'], '<select')
			+ mb_substr_count($field['CONTROL'], '<textarea')
		);

		$fieldPushTitle = ($controlCount === 1) ? 'top' : null;
	}
}

if (isset($field['DEPEND']))
{
	Market\Ui\Assets::loadPlugin('Ui.Input.DependField');

	$rowAttributes['class'] = 'js-plugin';
	$rowAttributes['data-plugin'] = 'Ui.Input.DependField';
	$rowAttributes['data-depend'] = Market\Utils::jsonEncode($field['DEPEND'], JSON_UNESCAPED_UNICODE);

	if ($field['DEPEND_HIDDEN'])
	{
		$rowAttributes['class'] .= ' is--hidden';
	}
}

if (isset($field['INTRO']))
{
	?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r" width="60%">
			<small><?= $field['INTRO']; ?></small>
		</td>
	</tr>
	<?php
}
?>
<tr <?= Market\Ui\UserField\Helper\Attributes::stringify($rowAttributes); ?>>
	<td class="adm-detail-content-cell-l <?= $hasAdditionalRow ? 'pos-inner--bottom' : '' ?> <?= $fieldPushTitle ? 'push--' . $fieldPushTitle : '' ?>" width="40%" align="right" valign="<?= $fieldValign ?>">
		<?php
		include __DIR__ . '/field-title.php';
		?>
	</td>
	<td class="adm-detail-content-cell-r <?= $hasAdditionalRow ? 'pos-inner--bottom' : ''; ?>" width="60%">
		<?php
		if ($fieldControl !== null)
		{
			echo $fieldControl['CONTROL'];
		}

		if (!empty($field['SETTINGS']['BUTTONS']))
		{
			$buttons = $field['SETTINGS']['BUTTONS'];

			include __DIR__ . '/field-buttons.php';
		}
		?>
	</td>
</tr>
<?php

if ($hasAdditionalRow)
{
	?>
	<tr>
		<td class="adm-detail-content-cell-l pos-inner--top" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r pos-inner--top" width="60%">
			<?php
			if ($hasDescription)
			{
				echo '<small>' . $field['DESCRIPTION'] . '</small>';
			}

			if ($hasNote)
			{
				echo BeginNote();
				echo $field['NOTE'];
				echo EndNote();
			}
			?>
		</td>
	</tr>
	<?php
}