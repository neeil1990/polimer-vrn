<?
namespace Arturgolubev\Ozon; //2.2.4

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

class Settings {
	static function getSites(){
		$result = array();
		
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
		while($arRes = $rsSites->Fetch()){
			$result[] = array(
				"ID" => $arRes["ID"],
				"NAME" => $arRes["NAME"],
			);
		}
		
		return $result;
	}
	
	static function checkModuleDemo($module_id, $text){
		if(!Loader::includeModule($module_id)){
			\CAdminMessage::ShowMessage(array("MESSAGE" => $text, 'HTML' => true, 'TYPE' => 'ERROR'));
		}
	}
	
	static function showInitUI(){
		if (class_exists('\Bitrix\Main\UI\Extension')) {
		   \Bitrix\Main\UI\Extension::load("ui.hint");
		   ?>
			<script>BX.ready(function() {BX.UI.Hint.init(BX('adm-workarea')); });</script>
			<style>.simple-hint {display: none !important;}</style>
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
			#bx-admin-prefix .adm-info-message-red .adm-info-message {
				color: #111;
			}
			#bx-admin-prefix form .adm-detail-content-wrap select, #bx-admin-prefix form  .adm-detail-content-wrap input[type=text] {
				max-width: 350px;
				min-width: 200px;
			}
			#bx-admin-prefix .help_note_wrap .adm-info-message {
				display: block;
				color: #111;
				font-size: 14px;
				line-height: 20px;
				background: #fff;
				border-color: #f5f9f9;
			}
			#bx-admin-prefix .help_note_wrap .adm-info-message .title {
				font-weight: bold;
				font-size: 15px;
			}
			#bx-admin-prefix .help_note_wrap .adm-info-message p {
				margin: 5px 0;
			}
			
			#bx-admin-prefix option:checked {
				background-color: #0078d7;
				color: #fff;
			}
		</style>
		<?
	}
	
	static function AdmSettingsSaveOption($module_id, $arOption)
	{
		if(!is_array($arOption) || isset($arOption["note"]))
			return false;

		if($arOption[3][0] == "statictext" || $arOption[3][0] == "statichtml")
			return false;

		$arControllerOption = \CControllerClient::GetInstalledOptions($module_id);

		if(isset($arControllerOption[$arOption[0]]))
			return false;

		$name = $arOption[0];
		$isChoiceSites = array_key_exists(6, $arOption) && $arOption[6] == "Y" ? true : false;

		if ($isChoiceSites)
		{
			if (isset($_REQUEST[$name."_all"]) && $_REQUEST[$name."_all"] <> '')
				\COption::SetOptionString($module_id, $name, $_REQUEST[$name."_all"], $arOption[1]);
			else
				\COption::RemoveOption($module_id, $name);
			$queryObject = \Bitrix\Main\SiteTable::getList(array(
				'select' => array('LID', 'NAME'),
				'filter' => array(),
				'order' => array('SORT' => 'ASC'),
			));
			while ($site = $queryObject->fetch())
			{
				if (isset($_REQUEST[$name."_".$site["LID"]]) && $_REQUEST[$name."_".$site["LID"]] <> '' &&
					!isset($_REQUEST[$name."_all"]))
				{
					$val = $_REQUEST[$name."_".$site["LID"]];
					if($arOption[3][0] == "checkbox" && $val != "Y")
						$val = "N";
					if($arOption[3][0] == "multiselectbox")
						$val = @implode(",", $val);
					\COption::SetOptionString($module_id, $name, $val, $arOption[1], $site["LID"]);
				}
				else
				{
					\COption::RemoveOption($module_id, $name, $site["LID"]);
				}
			}
		}
		else
		{
			if(!isset($_REQUEST[$name]))
			{
				if($arOption[3][0] <> 'checkbox' && $arOption[3][0] <> "multiselectbox")
				{
					return false;
				}
			}

			$val = $_REQUEST[$name];

			if($arOption[3][0] == "checkbox" && $val != "Y")
				$val = "N";
			if($arOption[3][0] == "multiselectbox" && is_array($val))
				$val = @implode(",", $val);

			\COption::SetOptionString($module_id, $name, $val, $arOption[1]);
		}

		return null;
	}
	
	static function showSettingsList($module_id, $arOptions, $tab){
		$settingList = $arOptions[$tab["OPTIONS"]];
		
		if(!is_array($settingList)) return 0;
		foreach ($settingList as $Option) {
			// echo '<pre>'; print_r($Option); echo '</pre>';
			
			if($Option["3"]["0"] == 'statictext'){
				?>
				<tr>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%">
						<?=$Option["1"]?>
					</td>
					<td class="adm-detail-content-cell-r" width="50%">
						<?=htmlspecialchars_decode($Option["2"])?>
						<?if($Option[5]):?>
							<div style="margin-top: 4px; font-size: 11px;"><?=$Option[5]?></div>
						<?endif;?>
					</td>
				</tr>
				<?
			}elseif($Option["3"]["0"] == 'calendar'){
				\CJSCore::Init(array('date'));
				$value = \COption::GetOptionString($module_id, $Option[0],  $Option[2]);
				?>
					<tr>
						<td class="adm-detail-content-cell-l" width="50%"><?=$Option["1"]?><a name="opt_<?=$Option["0"]?>"></a></td>
						<td class="adm-detail-content-cell-r" width="50%">
							<?$pickID = "pick_date_".$Option["0"];?>
							
							<input autocomplete="off" type="text" id="<?=$pickID?>" size="" maxlength="255" value="<?=$value?>" name="<?=$Option["0"]?>" onclick="BX.calendar({node: this, field: this, bTime: false});">
						</td>
					</tr>
				<?
			}elseif($Option["3"]["0"] == 'colorbox'){
				\CJSCore::Init(array('color_picker'));
				$value = \COption::GetOptionString($module_id, $Option[0]);
				?>
					<tr>
						<td class="adm-detail-content-cell-l" width="50%"><?=$Option["1"]?><a name="opt_<?=$Option["0"]?>"></a></td>
						<td class="adm-detail-content-cell-r" width="50%">
							<?$pickID = "pick_color_".$Option["0"];?>
							
							<input autocomplete="off" type="text" id="<?=$pickID?>" size="" maxlength="255" value="<?=$value?>" name="<?=$Option["0"]?>">
							
							<script type="text/javascript">
							BX.ready(function() {
								var element = BX('<?=$pickID?>');

								BX.bind(element, 'focus', function () {
									new BX.ColorPicker({
										bindElement: element,
										defaultColor: '',
										allowCustomColor: true,
										onColorSelected: function (item) {
											element.value = item;
										},
										popupOptions: {
											angle: true,
											autoHide: true,
											closeByEsc: true,
										}
									}).open();
								});
							  });
							</script>
						</td>
					</tr>
				<?
			}elseif($Option["3"]["0"] == 'filepath'){
				$value = \COption::GetOptionString($module_id, $Option[0], $Option[2]);
				?>
					<tr>
						<td class="adm-detail-content-cell-l" width="50%"><?=$Option["1"]?><a name="opt_<?=$Option["0"]?>"></a></td>
						<td class="adm-detail-content-cell-r" width="50%">
							<?$opt = strtolower("filepath_".$Option["0"]);?>
							
							<?
							\CAdminFileDialog::ShowScript(Array(
								'event' => 'BX_FD_'.$opt,
								'arResultDest' => Array('FUNCTION_NAME' => 'BX_FD_ONRESULT_'.$opt),
								'arPath' => Array(),
								'select' => 'F',
								'operation' => 'O',
								'showUploadTab' => true,
								'showAddToMenuTab' => false,
								'fileFilter' => '',
								'allowAllFiles' => true,
								'SaveConfig' => true
							));
							?>
							
							<input autocomplete="off" type="text" id="visual_inp_<?=$opt?>" size="" maxlength="512" value="<?=$value?>" name="<?=$Option["0"]?>">
							<input value="<?=Loc::getMessage("MAIN_SELECT")?>" type="button" onclick="window.BX_FD_<?=$opt?>();" />
							
							<script>
								BX.ready(function() {
									if (BX("bx_fd_input_<?=($opt)?>"))
										BX("bx_fd_input_<?=($opt)?>").onclick = window.BX_FD_<?=($opt)?>;
								});
								
								window.BX_FD_ONRESULT_<?=$opt?> = function(filename, filepath)
								{
									var oInput = BX("visual_inp_<?=$opt?>");
									if (typeof filename == "object")
										oInput.value = filename.src;
									else
										oInput.value = (filepath + "/" + filename).replace(/\/\//ig, '/');
								}
							</script>
						</td>
					</tr>
				<?
			}else{
				// __AdmSettingsDrawRow($module_id, $Option);
				self::drawRow($module_id, $Option);
			}
		}
	}
	
	static function drawRow($module_id, $Option)
	{
		$arControllerOption = \CControllerClient::GetInstalledOptions($module_id);
		if(!is_array($Option)):
		?>
			<tr class="heading">
				<td colspan="2"><?=$Option?></td>
			</tr>
		<?
		elseif(isset($Option["note"])):
		?>
			<tr>
				<td colspan="2" align="center">
					<?echo BeginNote('align="center"');?>
					<?=$Option["note"]?>
					<?echo EndNote();?>
				</td>
			</tr>
		<?
		else:
			$isChoiceSites = array_key_exists(6, $Option) && $Option[6] == "Y" ? true : false;
			$listSite = array();
			$listSiteValue = array();
			if ($Option[0] != "")
			{
				if ($isChoiceSites)
				{
					$queryObject = \Bitrix\Main\SiteTable::getList(array(
						"select" => array("LID", "NAME"),
						"filter" => array(),
						"order" => array("SORT" => "ASC"),
					));
					$listSite[""] = Loc::getMessage("MAIN_ADMIN_SITE_DEFAULT_VALUE_SELECT");
					$listSite["all"] = Loc::getMessage("MAIN_ADMIN_SITE_ALL_SELECT");
					while ($site = $queryObject->fetch())
					{
						$listSite[$site["LID"]] = $site["NAME"];
						$val = \COption::GetOptionString($module_id, $Option[0], $Option[2], $site["LID"], true);
						if ($val)
							$listSiteValue[$Option[0]."_".$site["LID"]] = $val;
					}
					$val = "";
					if (empty($listSiteValue))
					{
						$value = \COption::GetOptionString($module_id, $Option[0], $Option[2]);
						if ($value)
							$listSiteValue = array($Option[0]."_all" => $value);
						else
							$listSiteValue[$Option[0]] = "";
					}
				}
				else
				{
					$val = \COption::GetOptionString($module_id, $Option[0], $Option[2]);
				}
			}
			else
			{
				$val = $Option[2];
			}
			if ($isChoiceSites):?>
			<tr>
				<td colspan="2" style="text-align: center!important;">
					<label><?=$Option[1]?></label>
				</td>
			</tr>
			<?endif;?>
			<?if ($isChoiceSites):
				foreach ($listSiteValue as $fieldName => $fieldValue):?>
				<tr>
				<?
					$siteValue = str_replace($Option[0]."_", "", $fieldName);
					self::drawLable($Option, $listSite, $siteValue);
					self::drawInput($Option, $arControllerOption, $fieldName, $fieldValue);
				?>
				</tr>
				<?endforeach;?>
			<?else:?>
				<tr>
				<?
					self::drawLable($Option, $listSite);
					self::drawInput($Option, $arControllerOption, $Option[0], $val);
				?>
				</tr>
			<?endif;?>
			<? if ($isChoiceSites): ?>
				<tr>
					<td width="50%">
						<a href="javascript:void(0)" onclick="addSiteSelector(this)" class="bx-action-href">
							<?=Loc::getMessage("MAIN_ADMIN_ADD_SITE_SELECTOR")?>
						</a>
					</td>
					<td width="50%"></td>
				</tr>
			<? endif; ?>
		<?
		endif;
	}
	
	
	static function drawInput($Option, $arControllerOption, $fieldName, $val)
	{
		$type = $Option[3];
		$disabled = array_key_exists(4, $Option) && $Option[4] == 'Y' ? ' disabled' : '';
		$bottom_text = array_key_exists(5, $Option) ? $Option[5] : '';
		?><td width="50%"><?
		if($type[0]=="checkbox"):
			?><input autocomplete="off" type="checkbox" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> id="<?echo htmlspecialcharsbx($Option[0])?>" name="<?=htmlspecialcharsbx($fieldName)?>" value="Y"<?if($val=="Y")echo" checked";?><?=$disabled?><?if($type[2]<>'') echo " ".$type[2]?>><?
		elseif($type[0]=="text" || $type[0]=="password"):
			?><input autocomplete="off" type="<?echo $type[0]?>"<?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> size="<?echo $type[1]?>" maxlength="512" value="<?echo htmlspecialcharsbx($val)?>" name="<?=htmlspecialcharsbx($fieldName)?>"<?=$disabled?><?=($type[0]=="password" || $type["noautocomplete"]? ' autocomplete="new-password"':'')?>><?
		elseif($type[0]=="selectbox"):
			$arr = $type[1];
			if(!is_array($arr))
				$arr = array();
			?><select autocomplete="off" name="<?=htmlspecialcharsbx($fieldName)?>" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> <?=$disabled?>><?
			foreach($arr as $key => $v):
				?><option value="<?echo $key?>"<?if($val==$key)echo" selected"?>><?echo htmlspecialcharsbx($v)?></option><?
			endforeach;
			?></select><?
		elseif($type[0]=="multiselectbox"):
			$arr = $type[1];
			if(!is_array($arr))
				$arr = array();
			$arr_val = explode(",",$val);
			?><select autocomplete="off" size="5" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> multiple name="<?=htmlspecialcharsbx($fieldName)?>[]"<?=$disabled?>><?
			foreach($arr as $key => $v):
				?><option value="<?echo $key?>"<?if(in_array($key, $arr_val)) echo " selected"?>><?echo htmlspecialcharsbx($v)?></option><?
			endforeach;
			?></select><?
		elseif($type[0]=="textarea"):
			?><textarea autocomplete="off" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?=htmlspecialcharsbx($fieldName)?>"<?=$disabled?>><?echo htmlspecialcharsbx($val)?></textarea><?
		elseif($type[0]=="html"):
		
			echo self::showHtmlEditor($fieldName, $val, $Option[3][1]);
		elseif($type[0]=="statictext"):
			echo htmlspecialcharsbx($val);
		elseif($type[0]=="statichtml"):
			echo $val;
		endif;?>
			<?if($bottom_text):?>
				<div style="margin-top: 4px; font-size: 11px;"><?=$bottom_text?></div>
			<?endif;?>
		</td><?
	}
	
	static function showHtmlEditor($name, $value, $cfg){
		if(Loader::includeModule("fileman")){
			$id  = preg_replace("/[^a-z0-9]/i" ,'' ,$name);
			
			if(!is_array($cfg) || empty($cfg))
			{
				$cfg = array('Bold','Italic','Underline','RemoveFormat','CreateLink','DeleteLink','Image','Video','BackColor','ForeColor','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','InsertOrderedList','InsertUnorderedList','Outdent','Indent','StyleList','HeaderList','FontList','FontSizeList');
			}
			
			ob_start();
			echo   '<input type="hidden" name="'  .  $name  .  '[TYPE]" value="html">' ;
			$LHE  =  new  \CLightHTMLEditor;
			$LHE ->Show(array(
				'id'  =>  $id ,
				'width'  =>  '100%' ,
				'height'  =>  '250px' ,
				'inputName'  =>  $name ,
				'content'  => htmlspecialchars_decode($value),
				'bUseFileDialogs'  =>  false ,
				'bFloatingToolbar'  =>  false ,
				'bArisingToolbar'  =>  false ,
				'toolbarConfig'  =>  $cfg,
			));
			$s  = ob_get_contents();
			ob_end_clean();
			
			return $s;
		}
		else
		{
			return '<textarea name="'.$name.'" style="width: 100%; height: 250px;">'.$value.'</textarea>';
		} 
	}
	
	
	static function drawLable($Option, array $listSite, $siteValue = "")
	{
		$type = $Option[3];
		$isChoiceSites = array_key_exists(6, $Option) && $Option[6] == "Y" ? true : false;
		?>
		<?if ($isChoiceSites): ?>
		<script type="text/javascript">
			//TODO It is possible to modify the functions if necessary to clone different elements
			function changeSite(el, fieldName)
			{
				var tr = jsUtils.FindParentObject(el, "tr");
				var sel = jsUtils.FindChildObject(tr.cells[1], "select");
				sel.name = fieldName+"_"+el.value;
			}
			function addSiteSelector(a)
			{
				var row = jsUtils.FindParentObject(a, "tr");
				var tbl = row.parentNode;
				var tableRow = tbl.rows[row.rowIndex-1].cloneNode(true);
				tbl.insertBefore(tableRow, row);
				var sel = jsUtils.FindChildObject(tableRow.cells[0], "select");
				sel.name = "";
				sel.selectedIndex = 0;
				sel = jsUtils.FindChildObject(tableRow.cells[1], "select");
				sel.name = "";
				sel.selectedIndex = 0;
			}
		</script>
		<td width="50%">
			<select autocomplete="off" onchange="changeSite(this, '<?=htmlspecialcharsbx($Option[0])?>')">
				<?foreach ($listSite as $lid => $siteName):?>
					<option <?if ($siteValue ==$lid) echo "selected";?> value="<?=htmlspecialcharsbx($lid)?>">
						<?=htmlspecialcharsbx($siteName)?>
					</option>
				<?endforeach;?>
			</select>
		</td>
		<?else:?>
			<td<?if ($type[0]=="multiselectbox" || $type[0]=="html" || $type[0]=="textarea" || $type[0]=="statictext" ||
			$type[0]=="statichtml") echo ' class="adm-detail-valign-top"'?> width="50%"><?
			if ($type[0]=="checkbox")
				echo "<label for='".htmlspecialcharsbx($Option[0])."'>".$Option[1]."</label>";
			else
				echo $Option[1];
			?><a name="opt_<?=htmlspecialcharsbx($Option[0])?>"></a></td>
		<?endif;
	}
}