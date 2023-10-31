<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Maps;
use \Arturgolubev\Ozon\Unitools as UTools;
use \Arturgolubev\Ozon\Tools;
use \Arturgolubev\Ozon\Admin\SettingsHelper as SHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$module_id = 'arturgolubev.ozon';
Loader::IncludeModule($module_id);
CJSCore::Init(array("ag_ozon_options"));

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

$APPLICATION->SetTitle(Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TAB"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$sid = trim($_GET["sid"]);
$site_name = SHelper::checkSid($sid);

if(Tools::checkRights($sid, 'settings') && $site_name):
	$arLogTypes = Maps::getLogTypes($sid);
?>
	<div class="agwb_adm_page">
		<?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_NOTIFICATION")?>
		
		<?
		foreach($arLogTypes as $arLogType):
			$arLogs = array();
			$queryEventLogs = CEventLog::GetList(array("ID" => "DESC"), array("MODULE_ID" => $module_id, "AUDIT_TYPE_ID"=>$arLogType["TYPE"]."%"), array("nPageSize" => 15));
			while ($eventLog = $queryEventLogs -> fetch()) {
				$arLogs[] = $eventLog;
			}
		?>
				<div class="log_table_title"><?=$arLogType["NAME"]?> <a class="log_table_fullhref" href="/bitrix/admin/event_log.php?lang=ru&set_filter=Y&adm_filter_applied=0&find=<?=$arLogType["TYPE"]?>&find_type=audit_type_id" target="_blank"><?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_HREF_TITLE_STOCKS")?></a></div>
				<div class="console_text">
					<?foreach($arLogs as $logItem):?>
						<div class="console_text_item"><?=FormatDate('d F H:i', strtotime($logItem["TIMESTAMP_X"]));?> > <?=$logItem["DESCRIPTION"];?></div>
					<?endforeach;?>
				</div>
				<br/>
		<?endforeach;?>
		
		<br/>
		<div class="">
			<b><?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_EXT_TITLE")?></b>
			<?if(UTools::getSetting($sid."_debug_ext") == 'Y'):?>
				<?if(defined("LOG_FILENAME")):?>
					<?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_EXT_WORK_INFO", array('#filename#' => str_replace($_SERVER["DOCUMENT_ROOT"],'', LOG_FILENAME)))?>
				<?else:?>
					<?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_EXT_NODEFINE_INFO")?>
				<?endif;?>
			<?else:?>
				<?=Loc::getMessage("ARTURGOLUBEV_OZON_LOG_EXT_NOSETTING_INFO")?>
			<?endif;?>
		</div>
	</div>
<?else:?>
	<?=Loc::getMessage('ARTURGOLUBEV_OZON_RIGHTS_ERROR')?>
<?endif;?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');?>