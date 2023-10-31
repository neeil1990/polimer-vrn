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
use Bitrix\Main\UI\FileInput;

$module_id = 'zverushki.microm';

/* old */
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

	$aTabItem = array(
			"DIV" => "edit1",
			"TAB" => Loc::getMessage("MICROM_OPTION_TAB_EDIT_1"),
			"TITLE" => Loc::getMessage("MICROM_OPTION_TAB_EDIT_1_TITLE"),
			"OPTIONS" => array(
                "note0" => array(
                    'note0',  'Open Graph',
                    '',
                    array("note")
                ),
                    'open_graph' => [
                        'microm_view_open_graph',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VPROD"),
                        '',
                        array("checkbox", "open_graph", 'onclick="viewGroupElement(this, \'open_graph\')"')
                    ],
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
					"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_REL_microm_view_business_N"),
				)
			)
	);

	if (\Bitrix\Main\Loader::IncludeModule("catalog"))
	{

		$aTabItem["OPTIONS"]["note4"] = array(
					'note4',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_VPROD"),
					'',
					array("note")
				);
	    $aTabItem["OPTIONS"]["product"] =  array(
					'microm_view_product',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VPROD"),
					'',
					array("checkbox", "product", 'onclick="viewGroupElement(this, \'product\')"')
				);
	}
	$aTabItem["OPTIONS"]["note5"] = array(
					'note5',  Loc::getMessage("MICROM_OPTION_PARAM_HEAD_VINFO"),
					'',
					array("note")
				);
	$aTabItem["OPTIONS"]["info"] =  array(
					'microm_view_business',  Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME_VINFO"),
					'',
					array("checkbox", "info", 'onclick="viewGroupElement(this, \'info\')"')
				);
	$arPropertyHL = array(
            'open_graph' => [
                "open_graph_default_picture" => array(
                    "name" => Loc::getMessage('MICROM_OPTION_PARAM_FIELD_OG_PICTURE'),
                    "default" =>  "",
                    "type" => "picture"
                ),
                "open_graph_include" => array(
                    "name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_OG_INCLUDE"),
                    "default" => array(),
                    "type" => "group",
                ),
                "open_graph_twitter" => array(
                    "name" => Loc::getMessage('MICROM_OPTION_PARAM_FIELD_OG_TWITTER'),
                    "default" =>  "",
                    "type" => "checkbox",
                ),
            ],
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
			"sku" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_SKU"),
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
			"pageUrl" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PAGE_URL"),
				"default" => array(),
				"type" => "text"
			),
			"@id" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ID"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"name" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_NAME"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"logo" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LOGO"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"image" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_IMG"),
				"default" => array(),
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_IMG"),
			),
			"address" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ADDRESS"),
				"default" => array(),
				"type" => "group",
			),
			"address_streetAddress" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_STREET"),
				"default" => array(),
				"type" => "textarea",
				"request" => "Y"
			),
			"address_addressLocality" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LOCALITY"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"address_addressRegion" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_REGION"),
				"default" => array(),
				"type" => "text",
			),
			"address_postalCode" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PCODE"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"address_addressCountry" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_COUNTRY"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"url" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_URL"),
				"default" => array(),
				"type" => "text",
				"request" => "Y"
			),
			"telephone" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PHONE"),
				"default" => array(),
				"type" => "text",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_PHONE"),
			),
			"email" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_EMAIL"),
				"default" => array(),
				"type" => "text",
			),
			"geo" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_GEO"),
				"default" => array(),
				"type" => "group",
			),
			"geo_latitude" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LATITUDE"),
				"default" => array(),
				"type" => "text",
			),
			"geo_longitude" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_LONGITUDE"),
				"default" => array(),
				"type" => "text",
			),
			"util" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_UTIL"),
				"default" => array(),
				"type" => "group",
			),
			"priceRange" => array(
				"name" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_PRICE_RANG"),
				"default" => array(),
				"type" => "text",
				"request" => "Y",
				"description" => Loc::getMessage("MICROM_OPTION_PARAM_FIELD_DESC_PRICE_RANG"),
			),
		)
	);

	$rsSites = CSite::GetList($by = "sort", $order = "asc");
    while ($arSite = $rsSites->Fetch()) {
    	$tmp = $aTabItem;
        $tmp["DIV"] = "edit" . $arSite["LID"];
        $tmp["TAB"] = "[" . $arSite["LID"] . "] " . $arSite["NAME"];
        $tmp["LID"] = $arSite["LID"];
        $tmp["LID"] = $arSite["LID"];
        $tmp["OPTIONS"]["article"]["description"] = Loc::getMessage("MICROM_OPTION_PARAM_FIELD_REL_microm_view_business_".Option::get($module_id, "microm_view_article", "N", $arSite["LID"]));

    	$aTabs[] = $tmp;

    	$arPropertyHL["info"]["@id"]["default"][$arSite["LID"]] = "http".($request->isHttps() ? "s": "")."://".($arSite["SERVER_NAME"] ? $arSite["SERVER_NAME"] : Option::get("main", "server_name"));
    	$arPropertyHL["info"]["url"]["default"][$arSite["LID"]] = $arPropertyHL["info"]["@id"]["default"][$arSite["LID"]];
    	$arPropertyHL["info"]["name"]["default"][$arSite["LID"]] = $arSite["SITE_NAME"] ? $arSite["SITE_NAME"] : Option::get("main", "site_name");
    	$arPropertyHL["info"]["address_addressCountry"]["default"][$arSite["LID"]] = strtoupper($arSite["LANGUAGE_ID"] ?  $arSite["LANGUAGE_ID"] : Option::get("main", "admin_lid"));
    	$arPropertyHL["info"]["email"]["default"][$arSite["LID"]] = $arSite["EMAIL"];
    }

	if($request->isPost() && $request["Update"] && check_bitrix_sessid()):
		$error = array();
		$saveValue = true;

		$postList = $request->getPost('form');
		foreach ($aTabs as $aTab) {
			$post = $postList[$aTab["LID"]];
			foreach ($aTab["OPTIONS"] as $arOption) {
				if(!is_array($arOption))continue;
				if($arOption[3][0] === "note")continue;
				if(!empty($arOption["rel"])):
					$optionName = $arOption[0];
					$optionValue = $post[$optionName];
					if($optionValue === "Y"){
						$optionValue = $post[$arOption["rel"]];
						if($optionValue !== "Y"){
							$saveValue = false;
							$error[$aTab["LID"]][] = Loc::getMessage("MICROM_OPTION_PARAM_FIELD_ERROR_".$arOption["rel"]);
						}
					}
				endif;
			}
		}
		if(empty($error) && $saveValue):
			foreach ($aTabs as $aTab) {
				$post = $postList[$aTab["LID"]];
				foreach ($arPropertyHL as $key => $arProp):
					if($post[$aTab["OPTIONS"][$key][0]] == "Y"):
						$arrSave = array();
						foreach ($arProp as $k => $v) {
                            if ($v['type'] == 'picture') {
                                $value = $post["microm_".$k];

                                if (isset($value['tmp_name'])) {
                                    $arPicture = CIBlock::makeFileArray($value);
                                    $arPicture['MODULE_ID'] = 'zverushki.microm';

                                    $arrSave[$k] = CFile::SaveFile($arPicture, 'zverushki.microm');
                                } elseif ($_REQUEST['form_del'][$aTab["LID"]]["microm_".$k] == 'Y') {
                                    CFile::Delete($value);
                                    $arrSave[$k] = '';
                                } elseif (is_numeric($value)) {
                                    $arrSave[$k] = $value;
                                }

                            } else {
							    $arrSave[$k] = htmlspecialcharsEx(trim($post["microm_".$k]));
                            }
						}
						$arField = array(
							"SITE_ID" => $aTab["LID"],
							"CODE" => $key,
							"VALUE" => $arrSave
						);
						$result = MicromTable::getList(array(
								"select"	=> array("ID"),
								"filter"	=> array("SITE_ID" => $aTab["LID"], "CODE" => $key),
								"limit"		=> array()
							)
						);
						if($arr = $result->fetch())
							$result = MicromTable::Update($arr["ID"], $arField);
						else
							$result = MicromTable::Add($arField);

						if(!$result->isSuccess()){
							$saveValue = false;
							$errors = $result->getErrorMessages();
							$strWarning = "";
							foreach ($errors as $er) {
								if(is_array($er)){
									$strWarning .= implode("<br>",$er);
								}else{
									$strWarning .= trim($er);
								}
							}
							$error[$aTab["LID"]][] = $strWarning;
							break;
						}
					endif;
				endforeach;
				if($saveValue)
					foreach ($aTab["OPTIONS"] as $arOption) {
						if(!is_array($arOption))continue;
						if($arOption[3][0] === "note")continue;
						$optionName = $arOption[0];
						$optionValue = $post[$optionName];
						Option::set($module_id, $optionName, (is_array($optionValue) ? implode(" ,", $optionValue) : $optionValue), $aTab["LID"]);
					}
			}
		endif;
		if($saveValue){
			$cacheManager = Application::getInstance()
							->getTaggedCache();
			$cacheManager->clearByTag('micromCache');
			LocalRedirect($APPLICATION->GetCurPageParam("lang=".$request["lang"]."&mid=".$request["mid"]."&tabControl_active_tab=edit1&okSave=Y", array("lang", "mid", "okSave")));
		}else{
			foreach ($aTabs as $aTab){
				if(!empty($error[$aTab["LID"]])){
					CAdminMessage::ShowMessage(array(
			            "MESSAGE" => $aTab["TAB"],
			            "DETAILS" => implode("<br>", $error[$aTab["LID"]]),
			            "HTML" => true,
			            "TYPE" => "ERROR",
			         ));
				}
			}
		}
	endif;
	if($request["okSave"] === "Y"){
		$articleMess = "";
		foreach ($aTabs as $aTab){
			 $articleMess = Option::get($module_id, "microm_view_article", "N", $aTab["LID"]) == "Y" ? " ".LOC::getMessage("MICROM_OPTION_PARAM_FIELD_OK_microm_view_business") : "";
			 break;
		}
		CAdminMessage::ShowMessage(array(
            "MESSAGE" => LOC::getMessage("MICROM_OPTION_PARAM_FIELD_OK").$articleMess,
            "HTML" => true,
            "TYPE" => "OK",
         ));
	}
	$tabControl = new CAdminTabControl('tabControl', $aTabs);

	?><? $tabControl->Begin(); ?>
	<form method="post" action='<? echo $APPLICATION->GetCurPageParam("lang=".$request["lang"]."&mid=".$request["mid"], array("lang", "mid", "okSave")) ?>' name="microm_settings">
		<?foreach ($aTabs as $key => $aTab):
			?><?$tabControl->BeginNextTab();

			$post = $request->getPost('form');
			foreach ($aTab["OPTIONS"] as $key => $field):
				$val = Option::get($module_id, $field[0], "", $aTab["LID"]);
				if(empty($val))$val = $field[2];
				if($request->isPost())
					$val = $post[$aTab["LID"]][$field[0]];

				// selectbox
				switch ($field[3][0]) {
					case 'checkbox':
						?><tr>
							<td width="50%" class="adm-detail-content-cell-l"><label for="form_<? echo $aTab["LID"]?>_<? echo $field[0]?>"><? echo $field[1]?></label><a name="opt_<? echo $field[0]?>"></a></td>
							<td width="50%" class="adm-detail-content-cell-r">
								<input<?if($val == "Y"):?> checked="checked"<?endif?> type="checkbox" <? echo $field[3][2]?> value="Y" name="form[<? echo $aTab["LID"]?>][<? echo $field[0]?>]" id="form_<? echo $aTab["LID"]?>_<? echo $field[0]?>" class="adm-designed-checkbox" data-lid="<? echo $aTab["LID"]?>"><label class="adm-designed-checkbox-label" for="form_<? echo $aTab["LID"]?>_<? echo $field[0]?>" title=""></label>
							</td>
						</tr><?
						if(!empty($field["description"])){
							?><tr><td colspan="2" align="center"><div class="adm-info-message-wrap"><div class="adm-info-message"><?=$field["description"]?></div></div></td></tr><?
						}

						?><? _hlGetHtml($aTab["LID"], $arPropertyHL[$field[3][1]], $field[3][1], $request, $val)?><?
						break;
					case 'selectbox':
						?><tr>
							<td width="50%" class="adm-detail-content-cell-l"><label for="form_<? echo $aTab["LID"]?>_<? echo $field[0]?>"><? echo $field[1]?></label><a name="opt_<? echo $field[0]?>"></a></td>
							<td width="50%" class="adm-detail-content-cell-r">
								<select name="form[<? echo $aTab["LID"]?>][<? echo $field[0]?>]" class="typeselect"><?
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
		let _lid = $(this_).data("lid");
		if($(this_).is(':checked')){
			$('#group_'+_lid+'_'+name_).slideDown(200);
		}
		else{
			$('#group_'+_lid+'_'+name_).slideUp(200);
		}
		$(document).trigger('scroll');
	}
</script><?
function _hlGetHtml($lid, $arHtml, $name__, $request, $view){
	if(count((array)$arHtml) <= 0)return false;
	$result = MicromTable::getList(array(
			"filter"	=> array("SITE_ID" => $lid, "CODE" => $name__),
		)
	);
	$arr = $result->fetch();
	$arr = $arr["VALUE"];
	$post = $request->getPost('form');
	?><tr>
		<td width="100%" colspan="2">
			<div id="group_<? echo $lid?>_<? echo $name__?>" class="grouped_block"<?if($view != "Y"):?> style="display: none"<?endif?>>
				<table width="100%"><?
				foreach ($arHtml as $key => $item) {
					$item["value"] = $arr[$key];
					if(!isset($arr[$key]))$item["value"] = is_array($item["default"]) ? $item["default"][$lid] : $item["default"];
					if($request->isPost())
						$item["value"] = $post[$lid]["microm_".$key];
					?><tr><?
						switch ($item["type"]) {
							case 'picture':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="form_<? echo $lid?>_microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
                                    <?
                                    echo  FileInput::createInstance(array (
                                        "name"        => "form[$lid][microm_$key]",
                                        "description" => false,
                                        "upload"      => true,
                                        "allowUpload" => "I",
                                        "medialib"    => true,
                                        "fileDialog"  => true,
                                        "cloud"       => true,
                                        "delete"      => true,
                                        "maxCount"    => 1,
                                    ))->show(
                                        $item["value"]
                                    );?>
								</td>
								<?break;

							case 'text':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="form_<? echo $lid?>_microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
									<input type="text" value="<? echo $item["value"]?>" name="form[<? echo $lid?>][microm_<? echo $key?>]" class="microm_<? echo $key?>" size="40"/>
								</td>
								<?break;

							case 'textarea':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="form_<? echo $lid?>_microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
									<textarea type="text" name="form[<? echo $lid?>][microm_<? echo $key?>]" class="microm_<? echo $key?>" cols="50" rows="2"><? echo $item["value"]?></textarea>
								</td>
								<?break;

							case 'checkbox':?>
								<td width="50%" class="adm-detail-content-cell-l"><?
									if($item["description"]):?><span id="hint_<? echo $key?>"></span>
										<script type="text/javascript">	BX.hint_replace(BX('hint_<? echo $key?>'), '<? echo $item["description"]?>');</script>&nbsp;<?
									endif;
									?><label for="form_<? echo $lid?>_microm_<? echo $key?>"><? echo $item["name"]?><?if($item["request"]):?><span class="request-red">*</span><?endif?>:</label></td>
								<td width="50%" class="adm-detail-content-cell-r">
                                    <input type="checkbox" value="Y"<?=($item['value'] == 'Y' ? ' checked' : '');?> name="form[<? echo $lid?>][microm_<? echo $key?>]" class="microm_<? echo $key?>"/>
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