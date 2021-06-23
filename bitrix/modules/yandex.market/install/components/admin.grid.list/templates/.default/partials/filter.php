<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $component \Yandex\Market\Components\AdminGridList */

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

if (empty($arResult['FILTER'])) { return; }

$viewFilter = $component->getViewFilter();

?>
<form method="get" action="<?= $component->getBaseUrl(); ?>">
	<?php
	$viewFilter->Begin();

	foreach ($arResult['FILTER'] as $filter)
	{
		?>
		<tr>
			<td><?= $filter['name']; ?>:</td>
			<td>
				<?php
				switch ($filter['type'])
				{
					case 'custom':
						echo $component->getFilterHtml($filter);
					break;

					case 'list':
						?>
						<select name="<?= $filter['id']; ?>" id="<?= $filter['id']; ?>">
							<option value=""><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_GRID_LIST_FILTER_ANY'); ?></option>
							<?php
							foreach ($filter['items'] as $optionId => $optionValue)
							{
								?>
								<option value="<?= $optionId; ?>" <?= $optionId == $filter['value'] ? 'selected' : ''; ?>><?= Market\Utils::htmlEscape($optionValue); ?></option>
								<?php
							}
							?>
						</select>
						<?php
					break;

					case 'date':
						echo CalendarPeriod(
							$filter['id'] . '_from',
							isset($filter['value']['from']) ? $filter['value']['from'] : null,
							$filter['id'] . '_to',
							isset($filter['value']['to']) ? $filter['value']['to'] : null,
							'Y'
						);
					break;

					case 'number':
						?>
						<input type="text" name="<?= $filter['id']; ?>_from" size="10" value="<?= isset($filter['value']['from']) ? $filter['value']['from'] : ''; ?>">
						...
						<input type="text" name="<?= $filter['id']; ?>_to" size="10" value="<?= isset($filter['value']['to']) ? $filter['value']['to'] : ''; ?>">
						<?php
					break;

					default:
						?>
						<input type="text" name="<?= $filter['id']; ?>" size="30" value="<?= $filter['value']; ?>" />
						<?php
					break;
				}
				?>
			</td>
		</tr>
		<?php
	}

	$viewFilter->Buttons([
		'url' => $component->getBaseUrl(),
		'table_id' => $arParams['GRID_ID']
	]);

	$viewFilter->End();
	?>
</form>