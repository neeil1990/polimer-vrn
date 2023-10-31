<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Tools;
use \Arturgolubev\Ozon\Admin\SettingsHelper as SHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$module_id = 'arturgolubev.ozon';
Loader::IncludeModule($module_id);
CJSCore::Init(array("ag_ozon_options"));

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

$APPLICATION->SetTitle(Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TAB"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$sid = trim($_GET["sid"]);
$site_name = SHelper::checkSid($sid);

if(Tools::checkRights($sid, 'settings') && $site_name):
?>
<div class="agwb_adm_page">
	<?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENTS_NOTIFICATION")?>

	<table class="log_table" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td><b><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TABLE_NAME")?></b></td>
			<td><b><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TABLE_STATUS")?></b></td>
			<td><b><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TABLE_LAST_WORK")?></b></td>
			<td><b><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TABLE_NEXT_WORK")?></b></td>
		</tr>
		
		<?
		$arAgetnTypes = \Arturgolubev\Ozon\Maps::getAgentMap($sid);
		?>
		
		<?foreach($arAgetnTypes as $arItem):
			// echo '<pre>'; print_r($arItem); echo '</pre>';
		?>
			<?
			// echo '<pre>'; print_r($arItem); echo '</pre>';
			$res = CAgent::GetList(Array("ID" => "DESC"), array("MODULE_ID" => $module_id, "NAME"=>$arItem["NAME"]));
			if($arRes = $res->GetNext()) {
				?>
					<tr>
						<td><?=$arItem["TITLE"]?> (<a href="/bitrix/admin/agent_edit.php?ID=<?=$arRes["ID"]?>&lang=ru" target="_blank"><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_TABLE_EDIT")?></a>)</td>
						<?if($arRes["ACTIVE"] == 'Y'):?>
							<td><?=($arRes["LAST_EXEC"]) ? Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STATUS_WORKED") : Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STATUS_WAIT")?></td>
						<?else:?>
							<td><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STATUS_NO_ACTIVE")?></td>
						<?endif;?>
						<td><?=($arRes["LAST_EXEC"]) ? FormatDate('d F H:i', strtotime($arRes["LAST_EXEC"])) : '' ;?></td>
						<td><?=($arRes["NEXT_EXEC"]) ? FormatDate('d F H:i', strtotime($arRes["NEXT_EXEC"])) : '' ;?></td>
					</tr>
				<?
			}else{
				?>
					<tr>
						<td><?=$arItem["TITLE"]?></td>
						<td><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STATUS_NOT_FOUND")?></td>
						<td></td>
						<td></td>
					</tr>
				<?
			}
			?>
		<?endforeach;?>
	</table>
	<div class="log_table_description"><?=Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_EDIT_NOTE")?></div>
	
	<?
		//events control
		$events = \Arturgolubev\Ozon\Maps::getEventMap();
		
		// echo '<pre>'; print_r($events); echo '</pre>';
		
		$arShow = array();
		foreach($events as $event){
			foreach(GetModuleEvents($module_id, $event["event"], true) as $arEvent){
				$arShow[] = $event;
			}
		}
		?>
		
		<?if(count($arShow)):?>
			<br/>
			---
			<br/>
			<br/>
			<div class=""><b><?=Loc::getMessage("ARTURGOLUBEV_OZON_EVENT_ACTIVE_LIST_TITLE")?></b></div>
			<ul>
				<?foreach($arShow as $event):?>
					<li><?=$event['event']?> (<?=$event['name']?>)</li>
				<?endforeach;?>
			</ul>
		<?endif;?>
</div>
<?else:?>
	<?=Loc::getMessage('ARTURGOLUBEV_OZON_RIGHTS_ERROR')?>
<?endif;?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');?>