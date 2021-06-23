<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

/** @var $field array */

$rowAttributes = [];
$fieldControl = $component->getFieldHtml($field, null, true);
$hasDescription = isset($field['DESCRIPTION']);
$hasNote = isset($field['NOTE']);
$hasAdditionalRow = ($hasDescription || $hasNote);

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
	<td class="adm-detail-content-cell-l <?= $hasAdditionalRow ? 'pos-inner--bottom' : ''; ?>" width="40%" align="right" valign="<?= $fieldControl !== null && $fieldControl['VALIGN'] ? $fieldControl['VALIGN'] : 'middle'; ?>">
		<?php
		include __DIR__ . '/field-title.php';
		?>
	</td>
	<td class="adm-detail-content-cell-r <?= $hasAdditionalRow ? 'pos-inner--bottom' : ''; ?>" width="60%">
		<?= $fieldControl !== null ? $fieldControl['CONTROL'] : ''; ?>
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