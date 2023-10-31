<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web\Json;

if (empty($arResult['JS_MESSAGES'])) { return; }

?>
<script>
	BX.ready(function() {
		const registry = <?= Json::encode($arResult['JS_MESSAGES']) ?>;

		for (const [key, messages] of Object.entries(registry)) {
			const fieldClass = BX.YandexMarket.UI.EntityEditor[key];

			fieldClass.messages = Object.assign({}, fieldClass.messages, messages);
		}
	});
</script>
