<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $tab array */

if (isset($tab['DATA']['WARNING']))
{
	?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r" width="60%">
			<?php
			echo BeginNote('style="max-width: 550px; margin-top: -16px;"');
			echo $tab['DATA']['WARNING'];
			echo EndNote();
			?>
		</td>
	</tr>
	<?php
}