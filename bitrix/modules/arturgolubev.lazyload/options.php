<?
$module_id = 'arturgolubev.lazyload';

$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

global $USER, $APPLICATION;
if (!$USER->IsAdmin()) return;

$APPLICATION->SetAdditionalCSS("/bitrix/css/arturgolubev.lazyload/style.css");

$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
while($arRes = $rsSites->Fetch())
{
	$siteList[] = array(
		"ID" => $arRes["ID"],
		"NAME" => $arRes["NAME"],
	);
}

$arEffects = array(
	"fadeIn" => GetMessage($MODULE_NAME . "_FADE_IN_ENABLE"),
	"show" => GetMessage($MODULE_NAME . "_FADE_IN_DISABLED"),
);

$arAutoLoading = array(
	"disabled" => GetMessage($MODULE_NAME . "_SIMPLE_DISABLE"),
	"enabled" => GetMessage($MODULE_NAME . "_SIMPLE_ENABLE"),
);

$arPreLoading = array(
	"1" => GetMessage($MODULE_NAME . "_PRELOADING_1"),
	"100" => GetMessage($MODULE_NAME . "_PRELOADING_100"),
	"200" => GetMessage($MODULE_NAME . "_PRELOADING_200"),
	"300" => GetMessage($MODULE_NAME . "_PRELOADING_300"),
	"400" => GetMessage($MODULE_NAME . "_PRELOADING_400"),
	"500" => GetMessage($MODULE_NAME . "_PRELOADING_500"),
);

$arPreloaderImage = array();
$arPreloaderImage["loading"] = "/bitrix/images/arturgolubev.lazyload/loaders/loading.gif";
$arPreloaderImage["loading2"] = "/bitrix/images/arturgolubev.lazyload/loaders/loading2.gif";
$arPreloaderImage["loading3"] = "/bitrix/images/arturgolubev.lazyload/loaders/loading3.gif";
$arPreloaderImage["loading4"] = "/bitrix/images/arturgolubev.lazyload/loaders/loading4.gif";
$arPreloaderImage["loading5"] = "/bitrix/images/arturgolubev.lazyload/loaders/loading5.gif";

$arPreloaderImageSize = array(
	"pw64" => "64x64",
	"pw32" => "32x32",
	"pw16" => "16x16",
);

$arOptions = array(
    "main" => array(),
	// "visual" => array(),
	// "system" => array(),
);

$arOptions["main"][] = array("enable", GetMessage($MODULE_NAME . "_ENABLE"), "N", array("checkbox"));

if(COption::GetOptionString($module_id, "jquery") == 'Y')
	$arOptions["main"][] = array("jquery", GetMessage($MODULE_NAME . "_JQUERY"), "N", array("checkbox"));

if(COption::GetOptionString($module_id, "selectors"))
	$arOptions["main"][] = array("selectors", GetMessage($MODULE_NAME . "_SELECTORS"), "", array("textarea", 5, 40));

if(COption::GetOptionString($module_id, "page_exceptions"))
	$arOptions["main"][] = array("page_exceptions", GetMessage($MODULE_NAME . "_PAGE_EXCEPTION"), "", array("textarea", 5, 40));


$arOptions["main"][] = GetMessage($MODULE_NAME . "_IMAGE_VIEW_EFFECT");
$arOptions["main"][] = array("effect_type", GetMessage($MODULE_NAME . "_EFFECT_TYPE"), "", array("selectbox", $arEffects));
$arOptions["main"][] = array("effect_speed", GetMessage($MODULE_NAME . "_EFFECT_SPEED"), "500", array("text"));
		
$arOptions["main"][] = GetMessage($MODULE_NAME . "_PRELOADER_SETTING");
$arOptions["main"][] = array("disable_preloader", GetMessage($MODULE_NAME . "_DISABLE_PRELOADER"), "N", array("checkbox"));
$arOptions["main"][] = array("preloader_for_all", GetMessage($MODULE_NAME . "_SHOW_PRELOADER_FOR_ALL"), "N", array("checkbox"));
$arOptions["main"][] = array("preloader_image", GetMessage($MODULE_NAME . "_PRELOADER_IMAGE"), "loading", array("selectbox", $arPreloaderImage));
$arOptions["main"][] = array("preloader_image_size", GetMessage($MODULE_NAME . "_PRELOADER_IMAGE_SIZE"), "pw32", array("selectbox", $arPreloaderImageSize));

$arOptions["main"][] = GetMessage($MODULE_NAME . "_ADDITIONAL_FEATURES");
$arOptions["main"][] = array("auto_loading", GetMessage($MODULE_NAME . "_AUTO_LOADING"), "disabled", array("selectbox", $arAutoLoading));
$arOptions["main"][] = array("preloading", GetMessage($MODULE_NAME . "_PRELOADING"), "300", array("selectbox", $arPreLoading));
$arOptions["main"][] = array("note" => GetMessage($MODULE_NAME . "_PRELOADING_NOTE"));

$arOptions["main"][] = GetMessage($MODULE_NAME . "_SYSTEM_SETTINGS");
$arOptions["main"][] = array("debug", GetMessage($MODULE_NAME . "_DEBUG"), "N", array("checkbox"));






$arOptions["help"][] = array("help_card", GetMessage($MODULE_NAME . "_CARD_TEXT"), GetMessage($MODULE_NAME . "_CARD_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install", GetMessage($MODULE_NAME . "_INSTALL_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install_video", GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_faq", GetMessage($MODULE_NAME . "_FAQ_TEXT"), GetMessage($MODULE_NAME . "_FAQ_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_faq_main", GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT"), GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT_VALUE"), array("statictext"));


$arTabs = array(
    array("DIV" => "edit_main", "TAB" => GetMessage($MODULE_NAME ."_MAIN_TAB_SET"), "TITLE" => GetMessage($MODULE_NAME."_MAIN_TAB_TITLE_SET"), "OPTIONS"=>"main"),
);

foreach($siteList as $arSite){
	$optionKey = "setting_".$arSite["ID"];
	$arTabs[] = array("DIV" => "site_setting_".$arSite["ID"], "TAB" => GetMessage($MODULE_NAME."_SITE_SETTING").' "'.$arSite["NAME"].'"', "TITLE" => GetMessage($MODULE_NAME."_SITE_SETTING").' "'.$arSite["NAME"].'" ['.$arSite["ID"].']', "OPTIONS"=>$optionKey);
	
	$arOptions[$optionKey] = array();
	$arOptions[$optionKey][] = array("disabled_".$arSite["ID"], GetMessage($MODULE_NAME . "_DISABLED_SITE")." <b>".$arSite["NAME"]." [".$arSite["ID"]."]</b>", "N", array("checkbox"));
	$arOptions[$optionKey][] = array("jquery_".$arSite["ID"], GetMessage($MODULE_NAME . "_JQUERY"), "N", array("checkbox"));
	$arOptions[$optionKey][] = array("enable_iframe_".$arSite["ID"], GetMessage($MODULE_NAME . "_ENABLE_IFRAME"), "N", array("checkbox"));
	
	$arOptions[$optionKey][] = array("selectors_".$arSite["ID"], GetMessage($MODULE_NAME . "_SELECTORS"), "", array("textarea", 5, 40));
	$arOptions[$optionKey][] = array("page_exceptions_".$arSite["ID"], GetMessage($MODULE_NAME . "_PAGE_EXCEPTION"), "", array("textarea", 5, 40));
}

// $arTabs[] = array("DIV" => "edit_visual", "TAB" => GetMessage($MODULE_NAME."_VISUAL_TAB_SET"), "TITLE" => GetMessage($MODULE_NAME."_VISUAL_TAB_SET"), "OPTIONS"=>"visual");
// $arTabs[] = array("DIV" => "edit_system", "TAB" => GetMessage($MODULE_NAME."_ADDITIONAL_FEATURES"), "TITLE" => GetMessage($MODULE_NAME."_ADDITIONAL_FEATURES"), "OPTIONS"=>"system");

$arTabs[] = array("DIV" => "edit_system_help", "TAB" => GetMessage($MODULE_NAME."_HELP_TAB_NAME"), "TITLE" => GetMessage($MODULE_NAME."_HELP_TAB_TITLE"), "OPTIONS"=>"help");

$tabControl = new CAdminTabControl("tabControl", $arTabs);

// ****** SaveBlock
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && check_bitrix_sessid())
{
	CAdminNotify::Add(array('MESSAGE' => GetMessage($MODULE_NAME."_CLEAR_CACHE"),  'TAG' => $module_name."_clear_cache", 'MODULE_ID' => $module_id, 'ENABLE_CLOSE' => 'Y'));
	
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
			<?=GetMessage("ARTURGOLUBEV_LAZYLOAD_DEMO_IS_EXPIRED")?>
			<div class="adm-info-message-icon"></div>
		</div>
	</div>
<?}?>

<form class="lazy-setting-form" method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->Begin();?>
	
	<?foreach($arTabs as $key=>$tab):
		$tabControl->BeginNextTab();
			if(!class_exists('DOMDocument')):?>
			<tr>
				<td colspan="2">
					<div class="adm-info-message-wrap">
						<div class="adm-info-message">
							<?=GetMessage($MODULE_NAME . "_DOMD_NOT_FOUND")?>
						</div>
					</div>
				</td>
			</tr>
			<?endif;
			
			$settingList = $arOptions[$tab["OPTIONS"]];
			
			foreach ($settingList as $Option) {
				if($tab["OPTIONS"] == 'help'){
					?>
					<tr>
						<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%">
							<?=$Option["1"]?>
						</td>
						<td class="adm-detail-content-cell-r" width="50%">
							<?=htmlspecialchars_decode($Option["2"])?>
						</td>
					</tr>
					<?
				}else{
					if($Option[0] == 'preloader_image'):
						$optionValue = COption::GetOptionString($module_id,$Option[0]);
					?>
					<tr>
						<td class="adm-detail-content-cell-l"><?=$Option[1]?></td>
						<td class="adm-detail-content-cell-r">
							<?foreach($Option["3"]["1"] as $bg_key=>$bg_src):?>
								<div style="display:inline-block; text-align:center;">
									<label for="<?=$Option[0]?>_<?=$bg_key?>" class="ag-pw32 agll0708bg" style="width: 48px; height: 48px; margin: 5px; display: inline-block; background-image: url(<?=$bg_src?>);"></label>
									<br>
									<input type="radio" <?if($optionValue == $bg_key) echo 'checked';?> id="<?=$Option[0]?>_<?=$bg_key?>" value="<?=$bg_key?>" name="<?=$Option[0]?>">
								</div>
							<?endforeach;?>
						</td>
					</tr>
					<?
					else:
						__AdmSettingsDrawRow($module_id, $Option);
					endif;
				}
			}
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
	<?=GetMessage($MODULE_NAME."_SETTING_DOP_INFO");?>
<?=EndNote();?>

<?
if (class_exists('\Bitrix\Main\UI\Extension')) {
   \Bitrix\Main\UI\Extension::load("ui.hint");
   ?>
	<script>
	BX.ready(function() {
		BX.UI.Hint.init(BX('adm-workarea')); 
	});
	</script>
	<?
}
?>

<style>
	#bx-admin-prefix form .adm-info-message {
		color:#111;
		background:#fff;
		border: 1px solid #bbb;
		padding: 10px 15px;
		margin-top: 0;
		text-align: left;
	}
</style>
