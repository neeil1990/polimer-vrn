<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main;
use Yandex\Market;

/** @var Yandex\Market\Components\AdminGridList $component */

Market\Ui\Assets::loadPlugin('admin', 'css');
Market\Ui\Assets::loadPlugin('lib.dialog');

$adminList = $component->getViewList();

$adminList->BeginPrologContent();

if ($arResult['REDIRECT'] !== null)
{
	?>
	<script>
		window.top.location = <?= Main\Web\Json::encode($arResult['REDIRECT']); ?>;
	</script>
	<?php
}

if ($component->hasErrors())
{
	$component->showErrors();
}

if ($component->hasWarnings())
{
	$component->showWarnings();
}

$adminList->EndPrologContent();

if (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'loadMore')
{
	include __DIR__ . '/partials/load-mode-ajax.php';
}

$adminList->CheckListMode();

if ($arParams['USE_FILTER'])
{
	include __DIR__ . '/partials/filter.php';
}

if ($adminList instanceof CAdminSubList)
{
	include __DIR__ . '/partials/display-sublist.php';
}
else
{
	$adminList->DisplayList();
}