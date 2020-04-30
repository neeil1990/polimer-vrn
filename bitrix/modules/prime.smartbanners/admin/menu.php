<?
IncludeModuleLangFile(__FILE__);
$module_id = "prime.smartbanners";

if(CModule::IncludeModuleEx($module_id) == 3)
	return false;

{
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"sort" => 100,
		"url" => "nb_s_banner_list.php?lang=".LANGUAGE_ID,
		"text" => GetMessage("NB_MENU_MAIN"),
		"title" => GetMessage("NB_MENU_MAIN_TITLE"),
		"icon" => "prime_sticker_icon",
		"page_icon" => "prime_sticker_icon",
		"module_id" => $module_id,
		"items_id" => "menu_nb",
		"items" => array(),
	);


	$aMenu["items"][] = array(
		"text" => GetMessage("NB_BANNERS_LIST"),
		"url" => "nb_s_banner_list.php?lang=".LANGUAGE_ID,
		"more_url"=> array("nb_s_banner_list.php?lang=".LANGUAGE_ID,"nb_s_banner_edit.php?lang=".LANGUAGE_ID),
		"module_id" => $module_id,
		"title" => GetMessage("NB_BANNERS_LIST_TITLE"),
		"items_id" => "nb_banners_list",
	);
	$aMenu["items"][] = array(
		"text" => GetMessage("NB_BANNERS_STAT"),
		"url" => "nb_s_banner_stat.php?lang=".LANGUAGE_ID,
		"module_id" => $module_id,
		"title" => GetMessage("NB_BANNERS_STAT_TITLE"),
		"items_id" => "nb_banners_stat",
	);

	return $aMenu;
}
return false;
?>
