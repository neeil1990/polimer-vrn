<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

$adminList = $component->getViewList();

if (!($adminList instanceof CAdminUiList))
{
	ShowError('ui template only for CAdminUiList');
	return;
}

include __DIR__ . '/partials/prolog.php';

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	$arResult['GRID_PARAMETERS'],
	$component
);

?>
<script>
	(function() {
		window['<?= $arParams['GRID_ID'] ?>'] = new BX.publicUiList('<?= $arParams['GRID_ID'] ?>');
	})();
</script>
