<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

Market\Ui\Assets::loadPlugin('print', 'css');

$langPrefix = 'YANDEX_MARKET_T_TRADING_PRINT_PICKING_SHEET_';

?>
<h1><?= Loc::getMessage($langPrefix . 'TITLE') ?></h1>
<table>
	<thead>
		<tr>
			<?php
			foreach ($arResult['HEADERS'] as $header)
			{
				echo sprintf('<th>%s</th>', Loc::getMessage($langPrefix . $header));
			}
			?>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($arResult['TABLE'] as $row)
		{
			echo '<tr>';

			foreach ($arResult['HEADERS'] as $header)
			{
				$printValue = $row[$header] !== null ? $row[$header] : '&mdash;';
				$printValue = is_array($printValue) ? implode(', ', $printValue) : $printValue;

				echo sprintf('<td>%s</td>', $printValue);
			}

			echo '</tr>';
		}
		?>
		<tr></tr>
	</tbody>
</table>