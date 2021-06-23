<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

?>
<div class="adm-bus-table-container">
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
		<?php
		foreach ($arResult['PROPERTIES'] as $property)
		{
			?>
			<tr>
				<td class="adm-detail-content-cell-l" width="40%"><?= $property['NAME']; ?>:</td>
				<td class="adm-detail-content-cell-r"><?= htmlspecialcharsbx($property['VALUE']); ?></td>
			</tr>
			<?php
		}
		?>
	</table>
</div>