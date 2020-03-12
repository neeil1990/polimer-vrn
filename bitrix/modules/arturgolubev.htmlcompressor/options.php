<?
$module_id = 'arturgolubev.htmlcompressor';

$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

global $USER, $APPLICATION;
if (!$USER->IsAdmin()) return;


$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
while($arRes = $rsSites->Fetch())
{
	$siteList[] = array(
		"ID" => $arRes["ID"],
		"NAME" => $arRes["NAME"],
	);
}

$arOptions = array(
    "main" => array(
		array("compression_off", GetMessage("ARTURGOLUBEV_HTMLC_COMPRESSION_OFF"), "N", array("checkbox")),
	),
	"help" => array()
);

if(count($siteList))
{
	foreach($siteList as $arSite)
	{
		$arOptions["main"][] = array("compression_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_HTMLC_COMPRESSION_OFF_SITE")." <b>".$arSite["NAME"]." [".$arSite["ID"]."]</b>", "N", array("checkbox"));
	}
}

$arOptions["main"][] = GetMessage("ARTURGOLUBEV_HTMLC_REPLACE_PARAM");

$arOptions["main"][] = array("hide_pre", GetMessage("ARTURGOLUBEV_HTMLC_HIDE_PRE"), "N", array("checkbox"));
$arOptions["main"][] = array("hide_html_comment", GetMessage("ARTURGOLUBEV_HTMLC_HIDE_HTML_COMMENT"), "N", array("checkbox"));
$arOptions["main"][] = array("hide_script_type", GetMessage("ARTURGOLUBEV_HTMLC_SCRIPT_TYPE"), "N", array("checkbox"));
$arOptions["main"][] = array("javascript_compression_on", GetMessage("ARTURGOLUBEV_JAVASCRIPT_COMPRESSION_ON"), "N", array("checkbox"));
$arOptions["main"][] = array("page_exceptions", GetMessage("ARTURGOLUBEV_HTMLC_PAGE_EXCEPTIONS"), "", array("textarea",5,40));
$arOptions["main"][] = array("note" => GetMessage("ARTURGOLUBEV_HTMLC_PAGE_EXCEPTIONS_TEXT"));


$arOptions["help"][] = array("help_card", GetMessage($MODULE_NAME . "_CARD_TEXT"), GetMessage($MODULE_NAME . "_CARD_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install", GetMessage($MODULE_NAME . "_INSTALL_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install_video", GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_faq_main", GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT"), GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT_VALUE"), array("statictext"));


$arTabs = array();
$arTabs[] = array("DIV" => "edit1", "TAB" => GetMessage("ARTURGOLUBEV_HTMLC_OPTIONS_MAINTAB"), "TITLE" => GetMessage("ARTURGOLUBEV_HTMLC_OPTIONS_MAINTAB"), "OPTIONS"=>"main");
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
			<?=GetMessage($MODULE_NAME."_DEMO_IS_EXPIRED")?>
			<div class="adm-info-message-icon"></div>
		</div>
	</div>
<?}?>


<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">
	<?
	$tabControl->Begin();
	foreach($arTabs as $key=>$tab):
		$tabControl->BeginNextTab();
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
					__AdmSettingsDrawRow($module_id, $Option);
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