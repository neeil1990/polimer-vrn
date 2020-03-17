<?
use \Arturgolubev\Ecommerce\Settings as SET;

$module_id = 'arturgolubev.ecommerce';

$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

global $USER, $APPLICATION;
if (!$USER->IsAdmin()) return;

if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("catalog") || !CModule::IncludeModule("sale"))
	die('required iblock, sale, catalog modules');

/* get site */
$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
while($arRes = $rsSites->Fetch())
{
	$siteList[] = array(
		"ID" => $arRes["ID"],
		"NAME" => $arRes["NAME"],
	);
}

/* get currency */
$arCurrencyList = array();
$arCurrencyList[] = GetMessage("ARTURGOLUBEV_EC_CONVERT_CURRENCY_EMPTY");
$arCurrencyAllowed = array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');
$dbRes = CCurrency::GetList($by = 'sort', $order = 'asc');
while ($arRes = $dbRes->GetNext())
{
	if (in_array($arRes['CURRENCY'], $arCurrencyAllowed))
		$arCurrencyList[$arRes['CURRENCY']] = $arRes['FULL_NAME'];
}


/* get catalog */
$arCatalogs = array();
$res = CCatalog::GetList(Array(), Array('IBLOCK_ACTIVE'=>'Y'), false, false, array("*", "OFFERS"));
while($ar_res = $res->Fetch())
{
	$arCatalogs[] = $ar_res["IBLOCK_ID"];
}

$arMainCatalogs = array();
foreach($arCatalogs as $catalogId)
{
	$arCatalogInfo = CCatalog::GetByID($catalogId);
	if($arCatalogInfo["OFFERS"] == 'N')
	{
		$arProps = array();
		$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arCatalogInfo["IBLOCK_ID"]));
		while ($prop_fields = $properties->GetNext())
		{
			if($prop_fields["PROPERTY_TYPE"] == 'F' || $prop_fields["PROPERTY_TYPE"] == 'N')
				continue;
			
			$arProps[] = $prop_fields;
		}
		
		$arCatalogInfo["PROPS"] = $arProps;
		
		$arMainCatalogs[] = $arCatalogInfo;
	}
}


/* make options */
$arOptions = array();
$arOptions["main"] = array();

$arOptions["main"][] = array("off_mode", GetMessage("ARTURGOLUBEV_EC_OFF_MODE"), "N", array("checkbox"));
$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_DATA_SECTION");

$orderNumVariants = array("selectbox", array(
	"ID"=> GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ID"),
	"ACCOUNT_NUMBER"=> GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ACCOUNT_NUMBER"),
));
$arOptions["main"][] = array("get_order_id_from", GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM"), "N", $orderNumVariants);

foreach($arMainCatalogs as $arCatalog)
{
	$arValues = array(
		"" => GetMessage("ARTURGOLUBEV_EC_BRAND_NO_SELECT"),
	);
	foreach($arCatalog["PROPS"] as $arProp){
		$arValues[$arProp["ID"]] = '['.$arProp["ID"].'] '.$arProp["NAME"];
	}
	
	$name = 'BRAND_PROPERTY_'.$arCatalog["IBLOCK_ID"];
	$arOptions["main"][] = array($name, GetMessage("ARTURGOLUBEV_EC_OPTIONS_BRAND_PROP").'<b>'.$arCatalog["NAME"].' ('.$arCatalog["LID"].')</b>', "", array("selectbox", $arValues));
}


$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_SYSTEM");
$arOptions["main"][] = array("debug", GetMessage("ARTURGOLUBEV_EC_DEBUG_MODE"), "N", array("checkbox"));
$arOptions["main"][] = array("off_ajax", GetMessage("ARTURGOLUBEV_EC_OFF_AJAX"), "N", array("checkbox"));
$arOptions["main"][] = array("off_ajax_order", GetMessage("ARTURGOLUBEV_EC_OFF_AJAX_ORDER"), "N", array("checkbox")); // TODO


$old_analitycs_version = COption::GetOptionString($module_id, "old_analitycs_version");
if($old_analitycs_version == 'Y')
{
	$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_OLD_VERSION");
	$arOptions["main"][] = array("old_analitycs_version", GetMessage("ARTURGOLUBEV_EC_OLD_ANALITYCS_VERSION"), "N", array("checkbox"));
	$arOptions["main"][] = array("note" => GetMessage("ARTURGOLUBEV_EC_OLD_VERSION_NOTE"));
}

$container = COption::GetOptionString($module_id, "container");
$yandex_target_order = COption::GetOptionString($module_id, "yandex_target_order");
if($container || $yandex_target_order)
{
	$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING");
	if($container)
		$arOptions["main"][] = array("container", GetMessage("ARTURGOLUBEV_EC_CONTAINER_NAME"), "dataLayer", array("text"));

	if($yandex_target_order)
		$arOptions["main"][] = array("yandex_target_order", GetMessage("ARTURGOLUBEV_EC_YA_TARGET_ORDER"), "", array("text"));
}


$order_page = COption::GetOptionString($module_id, "order_page");
if($order_page)
{
	$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING");
	$arOptions["main"][] = array("order_page", GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE"), "", array("text"));
	$arOptions["main"][] = array("note" => GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE_NOTE"));
}

$arTabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage("ARTURGOLUBEV_EC_OPTIONS_MAINTAB"), "TITLE" => GetMessage("ARTURGOLUBEV_EC_OPTIONS_MAINTAB"), "OPTIONS"=>"main"),
);


if(count($siteList))
{
	foreach($siteList as $arSite)
	{
		$key = "site_options_".$arSite["ID"];
		
		$arOptions[$key] = array();
		
		$arOptions[$key][] = array("off_mode_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_DISABLED_SITE")." <b>".$arSite["NAME"]." [".$arSite["ID"]."]</b>", "N", array("checkbox"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_ALL_SETTING");
		$arOptions[$key][] = array("order_page_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE"), "", array("text"));
		$arOptions[$key][] = array("note" => GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE_NOTE"));
		
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING");
		$arOptions[$key][] = array("ya_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_OFF"), "N", array("checkbox"));
		$arOptions[$key][] = array("container_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CONTAINER_NAME"), "dataLayer", array("text"));
		$arOptions[$key][] = array("yandex_target_order_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_TARGET_ORDER"), "", array("text"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING");
		$arOptions[$key][] = array("ga_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_GA_OFF"), "N", array("checkbox"));

		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_FACEBOOK_SETTING");
		$arOptions[$key][] = array("fb_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_FB_OFF"), "N", array("checkbox"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY");
		$arOptions[$key][] = array("convert_currency_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CONVERT_CURRENCY"), "", array("selectbox", $arCurrencyList));
		$arOptions[$key][] = array("note" => GetMessage("ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY_NOTE"));

		$arTabs[] = array("DIV" => "site_setting_".$key, "TAB" => GetMessage("ARTURGOLUBEV_EC_SITE_SETTING").' "'.$arSite["NAME"].'"', "TITLE" => GetMessage("ARTURGOLUBEV_EC_SITE_SETTING").' "'.$arSite["NAME"].'" ['.$arSite["ID"].']', "OPTIONS"=>$key);
	}
}



$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_CARD_TEXT"), GetMessage($MODULE_NAME . "_CARD_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_INSTALL_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_INSTALL_PAGE_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_PAGE_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_FAQ_TEXT"), GetMessage($MODULE_NAME . "_FAQ_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("text", GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT"), GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT_VALUE"), array("statictext"));

$arTabs[] = array("DIV" => "edit_system_help", "TAB" => GetMessage($MODULE_NAME."_HELP_TAB_NAME"), "TITLE" => GetMessage($MODULE_NAME."_HELP_TAB_TITLE"), "OPTIONS"=>"help");


$tabControl = new CAdminTabControl("tabControl", $arTabs);

// ****** SaveBlock
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && check_bitrix_sessid())
{
	CAdminNotify::Add(array('MESSAGE' => GetMessage("ARTURGOLUBEV_EC_CLEAR_CACHE"),  'TAG' => $module_name."_clear_cache", 'MODULE_ID' => $module_id, 'ENABLE_CLOSE' => 'Y'));

	foreach ($arOptions as $aOptGroup) {
		foreach ($aOptGroup as $option) {
			__AdmSettingsSaveOption($module_id, $option);
		}
	}
	
    if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0)
        LocalRedirect($_REQUEST["back_url_settings"]);
    else
        LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());
}
?>

<?if(!CModule::IncludeModule($module_id)){?>
	<div class="adm-info-message-wrap adm-info-message-red">
		<div class="adm-info-message">
			<div class="adm-info-message-title"><?//=GetMessage($MODULE_NAME . "_ALLOW_URL_FOPEN_NOT_FOUND")?></div>
			<?=GetMessage("ARTURGOLUBEV_EC_DEMO_IS_EXPIRED")?>
			<div class="adm-info-message-icon"></div>
		</div>
	</div>
<?}?>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->Begin();?>
	
	<?foreach($arTabs as $key=>$tab):
		$tabControl->BeginNextTab();
			SET::showSettingsList($module_id, $arOptions, $tab);
	endforeach;?>
	
	<?$tabControl->Buttons();?>
		<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
		
		<input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
		
		<?if(strlen($_REQUEST["back_url_settings"])>0):?>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
		<?endif?>
		
		<?=bitrix_sessid_post();?>
	<?$tabControl->End();?>
</form>

<?=BeginNote();?>
	<?=GetMessage("ARTURGOLUBEV_EC_OFF_AJAX_DESCRIPTION");?>
<?=EndNote();?>

<?SET::showInitUI();?>