<?
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PERFMON_STOP', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets/arturgolubev/watcher/index.php");

$module_id = htmlspecialcharsbx($_POST["module"]);
$check_all = (htmlspecialcharsbx($_POST["target"]) == 'all') ? 1 : 0;

global $USER;
if($module_id && $USER->IsAdmin())
{
	// return false;
	
	$linkToBuy = false;
	$linkToBuyUpdate = false;
	if(LANGUAGE_ID == "ru")
	{
		$linkToBuy = "https://marketplace.1c-bitrix.ru"."/tobasket.php?ID=#CODE#";
		$linkToBuyUpdate = "https://marketplace.1c-bitrix.ru"."/tobasket.php?ID=#CODE#&lckey=".md5("BITRIX".CUpdateClientPartner::GetLicenseKey()."LICENCE");
	}
	
	$arRequestedModules = array();
	
	$folders = array(
		"/bitrix/modules",
	);
	foreach($folders as $folder)
	{
		if(file_exists($_SERVER["DOCUMENT_ROOT"].$folder))
		{
			$handle = opendir($_SERVER["DOCUMENT_ROOT"].$folder);
			if($handle)
			{
				while (false !== ($dir = readdir($handle)))
				{
					if(!isset($arModules[$dir]) && is_dir($_SERVER["DOCUMENT_ROOT"].$folder."/".$dir) && $dir!="." && $dir!=".." && strpos($dir, ".") !== false)
					{
						$module_dir = $_SERVER["DOCUMENT_ROOT"].$folder."/".$dir;
						if($info = CModule::CreateModuleObject($dir))
						{
							$arModules[$dir]["MODULE_ID"] = $info->MODULE_ID;
							$arModules[$dir]["MODULE_NAME"] = $info->MODULE_NAME;
							$arModules[$dir]["MODULE_DESCRIPTION"] = $info->MODULE_DESCRIPTION;
							$arModules[$dir]["MODULE_VERSION"] = $info->MODULE_VERSION;
							$arModules[$dir]["MODULE_VERSION_DATE"] = $info->MODULE_VERSION_DATE;
							$arModules[$dir]["MODULE_SORT"] = $info->MODULE_SORT;
							$arModules[$dir]["MODULE_PARTNER"] = $info->PARTNER_NAME;
							$arModules[$dir]["MODULE_PARTNER_URI"] = $info->PARTNER_URI;
							$arModules[$dir]["IsInstalled"] = $info->IsInstalled();
							if(defined(str_replace(".", "_", $info->MODULE_ID)."_DEMO"))
							{
								$arModules[$dir]["DEMO"] = "Y";
								if($info->IsInstalled())
								{
									if(CModule::IncludeModuleEx($info->MODULE_ID) != MODULE_DEMO_EXPIRED)
									{
										$arModules[$dir]["DEMO_DATE"] = ConvertTimeStamp($GLOBALS["SiteExpireDate_".str_replace(".", "_", $info->MODULE_ID)], "SHORT");
									}
									else
										$arModules[$dir]["DEMO_END"] = "Y";
								}
							}
						}
					}
					
					if(strstr($arModules[$dir]["MODULE_ID"], 'arturgolubev.')){
						$arModules[$dir]["MODULE_SORT"] = 1;
					}
					
					if(!$check_all && !strstr($arModules[$dir]["MODULE_ID"], 'arturgolubev.')){
						unset($arModules[$dir]);
					}
					
					if(!$arModules[$dir]["IsInstalled"]){
						unset($arModules[$dir]);
					}
					
					if(isset($arModules[$dir])){
						$arRequestedModules[] = $info->MODULE_ID;
					}
				}
				closedir($handle);
			}
		}
	}
	uasort($arModules, function($a, $b){if($a["MODULE_SORT"] == $b["MODULE_SORT"]) return strcasecmp($a["MODULE_NAME"], $b["MODULE_NAME"]); return ($a["MODULE_SORT"] < $b["MODULE_SORT"])? -1 : 1;});
	
	$error = 0;
	$errorText = '';
	
	if(!empty($arRequestedModules)){
		$obCache = new CPHPCache();
		$cacheID = 'updateinfo'.$check_all; $cachePath = '/module_watcher/'.$cacheID;
		if($obCache->InitCache(3600, $cacheID, $cachePath)){
		   $vars = $obCache->GetVars();
		   $arModules = $vars['arModules'];
		}elseif($obCache->StartDataCache()){
			$stableVersionsOnly = COption::GetOptionString("main", "stable_versions_only", "Y");
			$tmp = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, $stableVersionsOnly, $arRequestedModules, Array("fullmoduleinfo" => "Y"));

			if($tmp["ERROR"]){
				$error = 1;
				$errorText = "<span class='red'>1c-Bitrix: ".$tmp["ERROR"][0]["#"].'</span>';
				
				$obCache->AbortDataCache();
			}else{
				if(is_array($tmp["MODULE"]) && !empty($tmp["MODULE"])){
					foreach($tmp["MODULE"] as $k=>$v){
						
						if(!$arModules[$v["@"]["ID"]]) continue;
						$arModules[$v["@"]["ID"]]["UPDATE_INFO"] = $v['@'];
						$arModules[$v["@"]["ID"]]["HAVE_UPDATES"] = (!empty($v["#"]));
					}
				}
				
				$obCache->EndDataCache(array('arModules' => $arModules));
			}
		}
	}
	
	if(!$error){
		$tableView = array(
			"demo_end" => array(),
			"demo_now" => array(),
			"update_end" => array(),
			"all_okey" => array(),
		);
		
		$cnt = 0;
		
		foreach($arModules as $arModule){
			if($arModule["DEMO"] == "Y"){
				if($arModule["DEMO_END"] == 'Y'){
					if($linkToBuy)
					{
						$arModule["IMPORTANT_INFO"] .= "<a href=\"".str_replace("#CODE#", $arModule["MODULE_ID"], $linkToBuy)."\" target=\"_blank\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_NEW_BUY")."</a>";
					}
					
					$tableView['demo_end'][] = $arModule;
				}else{
					$arModule["IMPORTANT_INFO"] = GetMessage("ARTURGOLUBEV_WATCHER_DEMO_TIME_OUT").$arModule["DEMO_DATE"];
					if($linkToBuy)
					{
						$arModule["IMPORTANT_INFO"] = "<span style=\"color:red;\">".$arModule["UPDATE_INFO"]["DATE_TO"]."</span><br /><a href=\"".str_replace("#CODE#", $arModule["MODULE_ID"], $linkToBuy)."\" target=\"_blank\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_NEW_BUY")."</a>";
					}
					
					if($arModule["HAVE_UPDATES"])
						$arModule["IMPORTANT_INFO"] .= "<br><span style=\"color:green;\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_HAVE_UPDATE_NOINSTALL")."</span>";
					
					$tableView['demo_now'][] = $arModule;
				}
				
				$cnt++;
			}
			elseif($arModule["UPDATE_INFO"]["UPDATE_END"])
			{
				if($linkToBuyUpdate)
				{
					$arModule["IMPORTANT_INFO"] = "<span style=\"color:red;\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_UPDATE_END").$arModule["UPDATE_INFO"]["DATE_TO"]."</span><br /><a href=\"".str_replace("#CODE#", $arModule["MODULE_ID"], $linkToBuyUpdate)."\" target=\"_blank\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_UPDATE_BUY")."</a>";
					
					if($arModule["HAVE_UPDATES"])
						$arModule["IMPORTANT_INFO"] .= "<br><span style=\"color:green;\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_HAVE_UPDATE_NOINSTALL")."</span>";
				}			
				
				$tableView['update_end'][] = $arModule;
				
				$cnt++;
			}
			elseif($arModule["HAVE_UPDATES"])
			{
				if($linkToBuyUpdate)
				{
					$arModule["IMPORTANT_INFO"] = "<span style=\"color:green;\">".GetMessage("ARTURGOLUBEV_WATCHER_MOD_HAVE_UPDATE")."</span>";
				}
				
				$tableView['have_updates'][] = $arModule;
				
				$cnt++;
			}
			else
			{
				// $tableView['all_okey'][] = $arModule;
			}
		}
		
		if($cnt == 0){
			$error = 1;
			$errorText = GetMessage("ARTURGOLUBEV_WATCHER_EMPTY_EVENTS");
		}
	}
	
	// echo '<pre>'; print_r($arModules); echo '</pre>';
	?>
	
	<?if($error):?>
		<div class="ag-watcher-error"><?=$errorText?></div>
	<?else:?>
		<?
		$index = 0;
		foreach($tableView as $tkey=>$arModType):
			if(empty($arModType)) continue;
			$index++;
		?>
			<div class="ag-watcher-title <?=($index>1)?'with-margin':''?>">
				<?=GetMessage("ARTURGOLUBEV_WATCHER_WARNING_".strtoupper($tkey))?>
			</div>
			
			<table cellpadding="0" cellpadding="0" class="ag-watcher-info">
				<tr>
					<th class="colname"><?=GetMessage("ARTURGOLUBEV_WATCHER_TABLE_COLUMN_NAME")?></td>
					<th class="colname"><?=GetMessage("ARTURGOLUBEV_WATCHER_TABLE_COLUMN_ACTION")?></th>
				</tr>
				<?foreach($arModType as $arModule):?>
					<tr>
						<td class="colname"><a target="_blank" href="http://marketplace.1c-bitrix.ru/solutions/<?=$arModule["MODULE_ID"]?>/"><?=$arModule["MODULE_NAME"]?></a> (<?=$arModule["MODULE_ID"]?>)</td>
						<td class="colcounter">
							<?=$arModule["IMPORTANT_INFO"]?>
						</td>
					</tr>
				<?endforeach;?>
			</table>
		<?endforeach;?>
		
		<div class="ag-watcher-morehref"><?=GetMessage("ARTURGOLUBEV_WATCHER_FULL_MODULE_LIST_HREF");?></div>
	<?endif;?>
<?}?>