<?
$module_id = 'arturgolubev.cssinliner';

$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

global $USER, $APPLICATION;
if (!$USER->IsAdmin()) return;

$arOptions = array(
    "main" => array(
        array("disable", GetMessage($MODULE_NAME . "_ENABLE"), "N", array("checkbox")),
    ),
	"help" => array()
);

$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
while($arRes = $rsSites->Fetch())
{
	$siteList[] = array(
		"ID" => $arRes["ID"],
		"NAME" => $arRes["NAME"],
	);
}

if(count($siteList))
{
	foreach($siteList as $arSite)
	{
		$arOptions["main"][] = array("disabled_".$arSite["ID"], GetMessage($MODULE_NAME . "_DISABLED_SITE")." <b>".$arSite["NAME"]." [".$arSite["ID"]."]</b>", "N", array("checkbox"));
	}
}


$arOptions["main"][] = GetMessage($MODULE_NAME . "_WORKING_WITH_STYLE");

$arOptions["main"][] = array("inline_max_weight", GetMessage($MODULE_NAME . "_INLINE_MAX_WEIGHT"), "512", array("text"));
$arOptions["main"][] = array("use_compress", GetMessage($MODULE_NAME . "_USE_COMPRESS"), "N", array("checkbox"));
$arOptions["main"][] = array("google_fonts_inline", GetMessage($MODULE_NAME . "_GOOGLE_FONTS_INLINE"), "N", array("checkbox"));
$arOptions["main"][] = array("outer_style_inline", GetMessage($MODULE_NAME . "_OUTER_STYLE_INLINE"), "N", array("checkbox"));


$move_js_to_body = COption::GetOptionString("main", "move_js_to_body");
if($move_js_to_body != 'Y')
	$arOptions["main"][] = array("note" => GetMessage($MODULE_NAME . "_NOTE_MAIN_JS"));

$arOptions["main"][] = GetMessage($MODULE_NAME . "_SYSTEM_TITLE");
$arOptions["main"][] = array("exceptions", GetMessage($MODULE_NAME . "_EXCEPTIONS"), "", array("textarea",5,40));
$arOptions["main"][] = array("admin_debug", GetMessage($MODULE_NAME . "_ADMIN_DEBUG"), "N", array("checkbox"));

$arOptions["help"][] = array("help_card", GetMessage($MODULE_NAME . "_CARD_TEXT"), GetMessage($MODULE_NAME . "_CARD_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install", GetMessage($MODULE_NAME . "_INSTALL_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_install_video", GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT"), GetMessage($MODULE_NAME . "_INSTALL_VIDEO_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_faq", GetMessage($MODULE_NAME . "_FAQ_TEXT"), GetMessage($MODULE_NAME . "_FAQ_TEXT_VALUE"), array("statictext"));
$arOptions["help"][] = array("help_faq_main", GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT"), GetMessage($MODULE_NAME . "_FAQ_MAIN_TEXT_VALUE"), array("statictext"));


$arTabs = array();
$arTabs[] = array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "TITLE" => GetMessage("MAIN_TAB_TITLE_SET"), "OPTIONS"=>"main");
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


$allow_url_fopen = ini_get("allow_url_fopen");
?>

<?if(!$allow_url_fopen):?>
	<div class="adm-info-message-wrap adm-info-message-red">
		<div class="adm-info-message">
			<div class="adm-info-message-title"><?=GetMessage($MODULE_NAME . "_ALLOW_URL_FOPEN_NOT_FOUND")?></div>
			<?=GetMessage($MODULE_NAME . "_ALLOW_URL_FOPEN_NOT_FOUND_TEXT")?>
			<div class="adm-info-message-icon"></div>
		</div>
	</div>
<?endif;?>


<?if(!CModule::IncludeModule($module_id)){?>
	<div class="adm-info-message-wrap adm-info-message-red">
		<div class="adm-info-message">
			<div class="adm-info-message-title"><?//=GetMessage($MODULE_NAME . "_ALLOW_URL_FOPEN_NOT_FOUND")?></div>
			<?=GetMessage($MODULE_NAME . "_DEMO_IS_EXPIRED")?>
			<div class="adm-info-message-icon"></div>
		</div>
	</div>
<?}?>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->Begin();?>
	
	<?foreach($arTabs as $key=>$tab):
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

<?= BeginNote();?>
<?=GetMessage($MODULE_NAME . "_MAIN_NOTE")?>
<?= EndNote();?>



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