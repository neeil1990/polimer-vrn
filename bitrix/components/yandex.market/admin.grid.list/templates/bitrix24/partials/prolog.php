<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;

if ($arResult['REDIRECT'] !== null)
{
	?>
	<script>
		window.top.location = <?= Main\Web\Json::encode($arResult['REDIRECT']); ?>;
	</script>
	<?php
}

if (!empty($arResult['LIST_EXTENSION']))
{
	$APPLICATION->AddHeadScript($arResult['TEMPLATE_PARENT_FOLDER'] . '/scripts/listextension.js');

	$extensionParameters =
		[ 'grid' => $adminList->table_id ]
		+ $arResult['LIST_EXTENSION'];

	?>
	<script>
		BX.ready(BX.defer(function() {
			const options = <?= Main\Web\Json::encode($extensionParameters) ?>;
			const AdminList = BX.namespace('YandexMarket.AdminList');

			new AdminList.ListExtension(options);
		}));
	</script>
	<?php
}
