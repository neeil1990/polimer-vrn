<style>
	.grouped_block {
	    background-color: #fff;
	    border: 1px solid #c4ced2;
	    margin: 0 20px;
	    padding: 15px;
	}
	.request-red{
		margin-left: 1px;
		color: red;
	}
</style><?

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	Zverushki\Microm\MicromTable;

$module_id = 'zverushki.microm';

$SITE_ID = false;
$SITE_NAME = false;

$rsSites = CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
if ($arSite = $rsSites->Fetch())
{
	$SITE_ID = $arSite["ID"];
	$COUNTRY = strtoupper($arSite["LANGUAGE_ID"] ?  $arSite["LANGUAGE_ID"] : Option::get("main", "admin_lid"));
	$SERVER_NAME = $arSite["SERVER_NAME"] ?  $arSite["SERVER_NAME"] : Option::get("main", "server_name");
	$SITE_NAME = $arSite["SITE_NAME"]? $arSite["SITE_NAME"] : Option::get("main", "site_name");
	$SITE_EMAIL = $arSite["EMAIL"];
}

define('MICROM_SITE_ID', $SITE_ID);
define('MICROM_SERVER_NAME', $SERVER_NAME);
define('MICROM_SITE_NAME', $SITE_NAME);
define('MICROM_SITE_EMAIL', $SITE_EMAIL);
define('MICROM_COUNTRY', $COUNTRY);

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);


CJSCore::Init(array('jquery'));
\Bitrix\Main\Loader::IncludeModule($module_id);
/*
	Проверяем права пользователя
 */
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($POST_RIGHT>="R") :
	$request = \Bitrix\Main\HttpApplication::GetInstance()->GetContext()->GetRequest();

	$aTabs = array(
		array(
			"DIV" => "edit1",
			"TAB" => Loc::getMessage("MICROM_OPTION_TAB_EDIT_1"),
			"TITLE" => Loc::getMessage("MICROM_OPTION_TAB_EDIT_1_TITLE"),
			"OPTIONS" => array(
				"note1" => array(
					'note1',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_FORMAT"),
					'',
					array("note")
				),
				"micromformat" => array(
					'microm_format_active',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_TYPEM"),
					'',
					array("selectbox", array("json-ld" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_VAL_JSD"), "microdata" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_VAL_MDATA"))),
					"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_MAIN_FORMAT"),
				),
				"note2" => array(
					'note2',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_VBREAD"),
					'',
					array("note")
				),
				"breadcrumbs" => array(
					'microm_view_breadcrumb',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VBREAD"),
					'',
					array("checkbox", "breadcrumbs", 'onclick="viewGroupElement(this, \'breadcrumb\')"')
				),
				"note3" => array(
					'note3',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_ARTICLE"),
					'',
					array("note")
				),
				"article" => array(
					'microm_view_article',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_ARTICLE"),
					'',
					array("checkbox", "article", ''),
					"rel" => "microm_view_business",
					"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_REL_microm_view_business_".Option::get($module_id, "microm_view_article", "N")),
				)
			)
		),
	);
	if (\Bitrix\Main\Loader::IncludeModule("catalog"))
	{

		$aTabs[0]["OPTIONS"]["note4"] = array(
					'note4',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_VPROD"),
					'',
					array("note")
				);
	    $aTabs[0]["OPTIONS"]["product"] =  array(
					'microm_view_product',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VPROD"),
					'',
					array("checkbox", "product", 'onclick="viewGroupElement(this, \'product\')"')
				);
	}
	$aTabs[0]["OPTIONS"]["note5"] = array(
					'note5',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_VINFO"),
					'',
					array("note")
				);
	$aTabs[0]["OPTIONS"]["info"] =  array(
					'microm_view_business',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VINFO"),
					'',
					array("checkbox", "info", 'onclick="viewGroupElement(this, \'info\')"')
				);
	$arPropertyHL = array(
		"product" => array(
			"manufacturer" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_MANUFACTURER"),
				"default" =>  "",
				"type" => "text"
			),
			"brand" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_BRAND"),
				"default" =>  "",
				"type" => "text"
			),
			"model" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_MODEL"),
				"default" =>  "",
				"type" => "text"
			),
			"vote_count" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_VOTE_COUNT"),
				"default" =>  "vote_count",
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_VOTE_COUNT"),
			),
			"rating" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_RAITING"),
				"default" =>  "rating",
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_RAITING"),
			),
		),
		"info" => array(
			"@id" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ID"),
				"default" =>  "http://".MICROM_SERVER_NAME,
				"type" => "text",
				"request" => "Y"
			),
			"name" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME"),
				"default" => MICROM_SITE_NAME,
				"type" => "text",
				"request" => "Y"
			),
			"logo" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LOGO"),
				"default" => "",
				"type" => "text",
				"request" => "Y"
			),
			"image" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_IMG"),
				"default" => "",
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_IMG"),
			),
			"address" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ADDRESS"),
				"default" => "",
				"type" => "group",
			),
			"address_streetAddress" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_STREET"),
				"default" => "",
				"type" => "textarea",
				"request" => "Y"
			),
			"address_addressLocality" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LOCALITY"),
				"default" => "",
				"type" => "text",
				"request" => "Y"
			),
			"address_addressRegion" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_REGION"),
				"default" => "",
				"type" => "text",
			),
			"address_postalCode" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PCODE"),
				"default" => "",
				"type" => "text",
				"request" => "Y"
			),
			"address_addressCountry" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_COUNTRY"),
				"default" => MICROM_COUNTRY,
				"type" => "text",
				"request" => "Y"
			),
			"url" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_URL"),
				"default" =>  "http://".Option::get("main", "server_name"),
				"type" => "text",
				"request" => "Y"
			),
			"telephone" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PHONE"),
				"default" => "",
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_PHONE"),
			),
			"email" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_EMAIL"),
				"default" => MICROM_SITE_EMAIL,
				"type" => "text",
			),
			"geo" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_GEO"),
				"default" => "",
				"type" => "group",
			),
			"geo_latitude" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LATITUDE"),
				"default" => "",
				"type" => "text",
			),
			"geo_longitude" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LONGITUDE"),
				"default" => "",
				"type" => "text",
			),
			"util" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_UTIL"),
				"default" => "",
				"type" => "group",
			),
			"priceRange" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PRICE_RANG"),
				"default" => "",
				"type" => "text",
				"request" => "Y",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_PRICE_RANG"),
			),
		)
	);
	if($request->isPost() && $request["Update"] && check_bitrix_sessid()):
		$error = array();
		$saveValue = true;

		foreach ($aTabs as $aTab) {
			foreach ($aTab["OPTIONS"] as $arOption) {
				if(!is_array($arOption))continue;
				if($arOption[3][0] === "note")continue;
				if(!empty($arOption["rel"])):
					$optionName = $arOption[0];
					$optionValue = $request->getPost($optionName);
					if($optionValue === "Y"){
						$optionValue = $request->getPost($arOption["rel"]);
						if($optionValue !== "Y"){
							$saveValue = false;
							CAdminMessage::ShowMessage(Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ERROR_".$arOption["rel"]));
						}
					}
				endif;
			}
		}
		if(empty($error) && $saveValue):
			foreach ($arPropertyHL as $key => $arProp):
				if($request->getPost($aTabs[0]["OPTIONS"][$key][0]) == "Y"):
					$arrSave = array();
					foreach ($arProp as $k => $v) {
						$arrSave[$k] = htmlspecialcharsEx(trim($request->getPost("microm_".$k)));
					}
					$arField = array(
						"SITE_ID" => MICROM_SITE_ID,
						"CODE" => $key,
						"VALUE" => $arrSave
					);
					$result = MicromTable::getList(array(
							"select"	=> array("ID"),
							"filter"	=> array("SITE_ID" => MICROM_SITE_ID, "CODE" => $key),
							"limit"		=> array()
						)
					);
					if($arr = $result->fetch())
						$result = MicromTable::Update($arr["ID"], $arField);
					else
						$result = MicromTable::Add($arField);

					if(!$result->isSuccess()){
						$saveValue = false;
						$error = $result->getErrorMessages();
						$strWarning = "";
						foreach ($error as $er) {
							if(is_array($er)){
								$strWarning .= implode("<br>",$er);
							}else{
								$strWarning .= trim($er);
							}
						}
						if (!empty($strWarning)){
							CAdminMessage::ShowMessage($strWarning);
						};
						break;
					}else{
						$saveValue = true;
					}
				endif;
			endforeach;

			if(empty($error) && $saveValue):
				foreach ($aTabs as $aTab) {
					foreach ($aTab["OPTIONS"] as $arOption) {
						if(!is_array($arOption))continue;
						if($arOption[3][0] === "note")continue;
						$optionName = $arOption[0];
						$optionValue = $request->getPost($optionName);
						Option::set($module_id, $optionName, (is_array($optionValue) ? implode(" ,", $optionValue) : $optionValue));
					}
				}
			endif;
		endif;
		if($saveValue){
			$cacheManager = Application::getInstance()
							->getTaggedCache();
			$cacheManager->clearByTag('micromCache');
			LocalRedirect($APPLICATION->GetCurPageParam("lang=".$request["lang"]."&mid=".$request["mid"]."&tabControl_active_tab=edit1&okSave=Y", array("lang", "mid", "okSave")));
		}
	endif;
	if($request["okSave"] === "Y"){
		// (Option::get($module_id, "microm_view_article", "N") == "Y"
		// CAdminMessage::ShowNote(LOC::getMessage("MICROM_OPTION_PARAM_FIELD_OK"));
		CAdminMessage::ShowMessage(array(
            "MESSAGE" => LOC::getMessage("MICROM_OPTION_PARAM_FIELD_OK").(
            		Option::get($module_id, "microm_view_article", "N") == "Y" ? " ".LOC::getMessage("MICROM_OPTION_PARAM_FIELD_OK_microm_view_business") : ""),
            "HTML" => true,
            "TYPE" => "OK",
         ));
	}
	$tabControl = new CAdminTabControl('tabControl', $aTabs);

	?><? $tabControl->Begin(); ?>
	<form method="post" action='<? echo $APPLICATION->GetCurPageParam("lang=".$request["lang"]."&mid=".$request["mid"], array("lang", "mid", "okSave")) ?>' name="microm_settings">
		<?foreach ($aTabs as $key => $aTab):?>
			<?$tabControl->BeginNextTab();?>
			<?foreach ($aTab["OPTIONS"] as $key => $field):
				$val = Option::get($module_id, $field[0]);
				if(empty($val))$val = $field[2];
				if($request->isPost())
					$val = $request->getPost($field[0]);

				// selectbox
				switch ($field[3][0]) {
					case 'checkbox':
						?><tr>
							<td width="50%" class="adm-detail-content-cell-l"><label for="<? echo $field[0]?>"><? echo $field[1]?></label><a name="opt_<? echo $field[0]?>"></a></td>
							<td width="50%" class="adm-detail-content-cell-r">
								<input<?if($val == "Y"):?> checked="checked"<?endif?> type="checkbox" <? echo $field[3][2]?> value="Y" name="<? echo $field[0]?>" id="<? echo $field[0]?>" class="adm-designed-checkbox"><label class="adm-designed-checkbox-label" for="<? echo $field[0]?>" title=""></label>
							</td>
						</tr><?
						if(!empty($field["description"])){
							?><tr><td colspan="2" align="center"><div class="adm-info-message-wrap"><div class="adm-info-message"><?=$field["description"]?></div></div></td></tr><?
						}
						?><? _hlGetHtml($arPropertyHL[$field[3][1]], $field[3][1], $request, $val)?><?
						break;
					case 'selectbox':
						?><tr>
							<td width="50%" class="adm-detail-content-cell-l"><label for="<? echo $field[0]?>"><? echo $field[1]?></label><a name="opt_<? echo $field[0]?>"></a></td>
							<td width="50%" class="adm-detail-content-cell-r">
								<select name="<? echo $field[0]?>" class="typeselect"><?
									foreach ($field[3][1] as $key => $option) {
										?><option value="<?=$key?>"<?if($val == $key):?> selected=""<?endif?>><?=$option?></option><?
									}
								?></select>
							</td>
							</tr><?
							if(!empty($field["description"])){
								?><tr><td colspan="2" align="center"><div class="adm-info-message-wrap"><div class="adm-info-message"><?=$field["description"]?></div></div></td></tr><?
							}
						break;
					case 'note':
						?><tr class="heading">
							<td colspan="2"><?
							?><? echo $field[1]?></td>
						</tr><?
						break;
				}
				?>
			<?endforeach;?>
		<?endforeach;?>
		<?
			$tabControl->Buttons();
		?>
		<input type="submit" name="Update" value="<? echo Loc::getMessage("MICROM_OPTION_BUTTON_UPD_NAME"); ?>" />
		<input type="reset" name="reset" value="<? echo Loc::getMessage("MICROM_OPTION_BUTTON_CANCEL_NAME"); ?>" />
		<? echo bitrix_sessid_post() ?>
	</form>
	<? $tabControl->End(); ?>
<?
endif
?><script>
	function viewGroupElement(this_, name_){
		if($(this_).is(':checked')){
			$('#group_'+name_).slideDown(200);
		}
		else{
			$('#group_'+name_).slideUp(200);
		}
		$(document).trigger('scroll');
	}
</script><?
function _hlGetHtml($arHtml, $name__, $request, $view){
	if(count($arHtml) <= 0)return false;
	$result = MicromTable::getList(array(
			"filter"	=> array("SITE_ID" => MICROM_SITE_ID, "CODE" => $name__),
		)
	);
	$arr = $result->fetch();
	$arr = $arr["VALUE"];

	?><tr>
		<td width="100%" colspan="2">
			<div id="group_<? echo $name__?>" class="grouped_block"<?if($view != "Y"):?> style="display: none"<?endif?>>
				<table width="100%"><?
				foreach ($arHtml as $key => $item) {
					$item["value"] = $arr[$key];
					if(!isset($arr[$key]))$item["value"] = $item["default"];
					if($request->isPost())
						$item["value"] = $request->getPost("microm_".$key)
					?><tr><?
						switch ($item["type"]) {
							case 'text':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
									<input type="text" value="<? echo $item["value"]?>" name="microm_<? echo $key?>" class="microm_<? echo $key?>" size="40"/>
								</td>
								<?break;

							case 'textarea':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
									<textarea type="text" name="microm_<? echo $key?>" class="microm_<? echo $key?>" cols="50" rows="2"><? echo $item["value"]?></textarea>
								</td>
								<?break;
							case 'group':?>
								<td width="100%" colspan="2" align="center"><label for="microm_<? echo $key?>"><b><? echo $item["name"]?></b></label></td>
								<?break;
						}
					?></tr><?
				}
				?></table>
			</div><?
		?></td><?
	?></tr><?
}
?>