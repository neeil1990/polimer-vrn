<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;

/** @var \Yandex\Market\Components\AdminGridList $component */
/** @var \CAdminUiList $adminList */

$adminList->BeginPrologContent();

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
	$APPLICATION->AddHeadScript($templateFolder . '/scripts/listextension.js');

	$extensionParameters =
		[ 'grid' => $adminList->table_id ]
		+ $arResult['LIST_EXTENSION'];

	?>
	<script>
		BX.ready(BX.defer(function() {
			var options = <?= Main\Web\Json::encode($extensionParameters) ?>;
			var AdminList = BX.namespace('YandexMarket.AdminList');

			new AdminList.ListExtension(options);
		}));
	</script>
	<?php
}

if ($component->hasErrors())
{
	foreach ($component->getErrors() as $message)
	{
		$adminList->AddUpdateError($message);
	}

	if ($arResult['EXCEPTION_MIGRATION'])
	{
		include __DIR__ . '/migration-form.php';
	}
}

if ($component->hasMessages())
{
	$component->showMessages();
}

if ($component->hasWarnings())
{
	$component->showWarnings();
}

$adminList->EndPrologContent();

$prologContent = $adminList->sPrologContent;

AddEventHandler('main', 'onAfterAjaxResponse', function() use ($prologContent) {
	echo $prologContent;
});