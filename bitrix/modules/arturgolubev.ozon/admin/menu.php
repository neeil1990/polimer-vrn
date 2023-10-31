<?
$module_name = 'arturgolubev.ozon';

if(!CModule::IncludeModule($module_name)){
	include_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/arturgolubev.ozon/autoload.php';
}

if(true){
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_name."/menu.php");
	
	$showMainButton = 0;
	
	$arIntegrationList = \Arturgolubev\Ozon\Tools::getMenuIntegrationsList();
	// echo '<pre>'; print_r($arIntegrationList); echo '</pre>';
	
	foreach($arIntegrationList as $integration){
		if($integration['rights_order'] || $integration['rights_settings']){
			$showMainButton = 1;
		}
		
		$arFormsList = array();
		
		if($integration['rights_settings']){
			$arFormsList[] = array(
				'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_SETTINGS"),
				'more_url' => array(),
				'url' => '/bitrix/admin/settings.php?lang='.LANG.'&mid='.$module_name.'&sid='.$integration["ID"],
				'icon' => 'sys_menu_icon',
			);
		}
		
		if($integration['token']){
			if($integration['rights_order']){
				$arFormsList[] = array(
					'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_ACTS"),
					'more_url' => array(),
					'url' => '/bitrix/admin/arturgolubev_ozon_acts.php?lang='.LANG.'&sid='.$integration["ID"],
					'icon' => 'fileman_sticker_icon',
				);
			}
			
			if($integration['rights_settings']){
				$arFormsList[] = array(
					'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_AGENTS"),
					'more_url' => array(),
					'url' => '/bitrix/admin/arturgolubev_ozon_agents.php?lang='.LANG.'&sid='.$integration["ID"],
					'icon' => 'iblock_menu_icon_settings',
				);
				$arFormsList[] = array(
					'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_LOGS"),
					'more_url' => array(),
					'url' => '/bitrix/admin/arturgolubev_ozon_logs.php?lang='.LANG.'&sid='.$integration["ID"],
					'icon' => 'iblock_menu_icon_settings',
				);
			}
		}

		if(count($arFormsList)){
			$arSubmenu[] = array(
				'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_SITE", array('#num#' => $integration['custom_name'])).' ['.$integration["ID"].']' ,
				'more_url' => array(),
				'icon' => 'sys_menu_icon',
				'items_id' => 'agoz_site_'.$integration["ID"],
				'items' => $arFormsList,
			);
		}
	}

	if($showMainButton){
		$arSubmenu[] = array(
			'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_DOCS"),
			'more_url' => array(),
			'url' => 'javascript: window.open("https://arturgolubev.ru/instructions/ozon/", "_blank"); void(0);',
			'icon' => 'update_marketplace',
		);
		$arSubmenu[] = array(
			'text' => GetMessage("ARTURGOLUBEV_OZON_SUBMENU_FAQ"),
			'more_url' => array(),
			'url' => 'javascript: window.open("https://arturgolubev.ru/knowledge/course33/", "_blank");  void(0);',
			'icon' => 'update_marketplace',
		);
		$aMenu = array(
			'parent_menu' => 'global_menu_services',
			'section' => 'arturgolubev_ozon',
			'sort' => 1,
			'text' => GetMessage("ARTURGOLUBEV_OZON_MENU_MAIN"),
			'icon' => 'arturgolubev_onoz_icon_main',
			'items_id' => 'agoz_icon_main',
			'items' => $arSubmenu,
		);

		return $aMenu;
	}
}