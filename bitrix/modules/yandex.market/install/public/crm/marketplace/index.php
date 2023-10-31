<?php
/**
 * @package yandex.market
 * @autoupdate yandex.market (remove this line if you need modify)
 */

use Bitrix\Main;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Main\UI\Extension::load('sidepanel');

$assetsManager = Main\Page\Asset::getInstance();
$assetsManager->addJs('/bitrix/js/yandex.market/crm/marketplace/bindings.js');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_after.php';
?><?php
$APPLICATION->IncludeComponent("yandex.market:crm.marketplace.menu", "");
?><?php
$APPLICATION->IncludeComponent("yandex.market:crm.trading.router", "", array(
	'SERVICE_CODE' => 'marketplace',
));
?><?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
