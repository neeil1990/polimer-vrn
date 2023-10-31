<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

$module_id = 'twinpx.yadelivery';

Loc::loadMessages(__FILE__);
Loader::includeModule($module_id);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($MODULE_RIGHT >= "R")
{
	$aMenu = array(
        'parent_menu' => 'global_menu_store',
        'sort' => 120,
        'text' => Loc::GetMessage('MODULE_TEXT'),
        'title' => Loc::GetMessage('MODULE_TITLE'),
        'icon' => 'sale_menu_icon_marketplace twinpx_menu_icon',
        'page_icon' => 'twinpx_page_icon',
        'module_id' => $module_id,
        'items_id' => 'menu_twinpx_delivery',
        'items' => array()
	);
	
	$aMenu['items'][] = array(
		'text' => Loc::GetMessage('ACTIVE_TEXT'),
		'title' => Loc::GetMessage('ACTIVE_TITLE'),
		'module_id' => $module_id,		
		'url' => 'twinpx_delivery_offers.php?lang='.LANGUAGE_ID,
	);
	$aMenu['items'][] = array(
		'text' => Loc::GetMessage('TEMPORAL_TEXT'),
		'title' => Loc::GetMessage('TEMPORAL_TITLE'),
		'module_id' => $module_id,		
		'url' => 'twinpx_delivery_temporal.php?lang='.LANGUAGE_ID,
	);
	$aMenu['items'][] = array(
		'text' => Loc::GetMessage('ARHIVE_TEXT'),
		'title' => Loc::GetMessage('ARHIVE_TITLE'),
		'module_id' => $module_id,		
		'url' => 'twinpx_delivery_archive.php?lang='.LANGUAGE_ID,
	);
}


?>
<? $APPLICATION->AddHeadString('<style>
.adm-submenu-item-name-link .adm-submenu-item-link-icon.twinpx_menu_icon {
	background: url(/bitrix/images/'.$module_id.'/yandex-menu-icon.svg) no-repeat center;
	width: 18px;
	margin-left: -5px;
 	margin-right: 9px;
}
</style>');?>
<?
return $aMenu;