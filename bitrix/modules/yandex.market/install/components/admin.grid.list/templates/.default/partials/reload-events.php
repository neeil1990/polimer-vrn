<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web\Json;

if (empty($arParams['RELOAD_EVENTS'])) { return; }

$events = (array)$arParams['RELOAD_EVENTS'];

?>
<script>
	(function() {
		const events = <?= Json::encode($events) ?>;
		const gridId = '<?= $arParams['GRID_ID'] ?>';

		for (const event of events) {
			BX.addCustomEvent(event, function() {
				if (window[gridId] == null) { return; }

				window[gridId].GetAdminList(window.location.href);
			});
		}
	})();
</script>