<?php
IncludeModuleLangFile(__FILE__);

class TheBrains {

    public function showBtn(&$menu){

        global $APPLICATION;
        /** @global $USER CUser */
        global $USER;

        if (!CModule::IncludeModule("iblock") || !$USER->IsAdmin()) {
            return false;
        }

        if ($_SERVER['REQUEST_METHOD']=='GET' && $APPLICATION->GetCurPage()=='/bitrix/admin/iblock_edit.php')
        {

            $res = CIBlock::GetByID($_REQUEST['ID']);
            if($ar_res = $res->GetNext())
                $name = $ar_res['NAME'];

            $db_iblock_type = CIBlockType::GetList();
            $select_ib_type = '';
            $select_ib_type .= '<select name="TYPE">';
            while($ar_iblock_type = $db_iblock_type->Fetch()){
                if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG)) {
                    $select_ib_type .= '<option value="'.$ar_iblock_type["ID"].'">';
                    $select_ib_type .= htmlspecialcharsex($arIBType["NAME"]);
                    $select_ib_type .= '</option>';
                }
            }
            $select_ib_type .= '</select>';

            $select_ib_wath_copy = '';
            $select_ib_wath_copy .= '<select name="W_COPY">';
            $select_ib_wath_copy .= '<option value="IB">'.GetMessage('THEBRAINSE_COPYIBLOCK_ONLY_IBLOCK').'</option>';
            $select_ib_wath_copy .= '<option value="SE">'.GetMessage('THEBRAINSE_COPYIBLOCK_ONLY_SECTION').'</option>';
            $select_ib_wath_copy .= '<option value="EL">'.GetMessage('THEBRAINSE_COPYIBLOCK_ALL_COPYY').'</option>';
            $select_ib_wath_copy .= '</select>';

            $menu[] = array(
                "ICON" => "btn_new",
                "MENU" => array(
                    array(
                        "TEXT" => GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY'),
                        "TITLE" => GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY'),
                        "ACTION" => self::htmlspecialchars("javascript:(new BX.CDialog({
					content_url: '" . $APPLICATION->GetCurPageParam("", array("mode", "table_id")) . "',
					width: 500,
					height: 180,
					resizable: false,
					draggable: true,
					content: '<h3 style=\"text-align: center\">". GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY') ." ID : ". $_REQUEST['ID'] ." ". $name ."</h3>"
					."<form style=\"text-align: center\" action=\"\" name=\"\">"
                    .$select_ib_wath_copy.""
					."<input type=\"hidden\" name=\"the_brains_copy_action\" value=\"copy\">"
					."<input type=\"hidden\" name=\"ID\" value=\"". $_REQUEST['ID'] ."\">"
					."<h3 style=\"text-align: center\">".GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY_TYPE_IB')."</h3>"
                    .$select_ib_type.""
					."</form>',
					buttons: [BX.CDialog.btnSave, BX.CDialog.btnCancel]
				})).Show()"),
                        "ICON" => "copy",
                    )
                ),

            );
        }
        return true;
    }

    function actionBtn(){

        /** @global $APPLICATION CMain */
        global $APPLICATION;
        /** @global $USER CUser */
        global $USER;

        if (!CModule::IncludeModule("iblock") || !$USER->IsAdmin()) {
            return false;
        }

        if($_REQUEST['the_brains_copy_action'] == 'copy') {
            $APPLICATION->RestartBuffer();

            if(intval($_REQUEST["ID"])>0){
                $bError = false;
                $IBLOCK_ID = intval($_REQUEST["ID"]);
                $ib = new CIBlock;
                $arFields = CIBlock::GetArrayByID($IBLOCK_ID);
                $arFields["GROUP_ID"] = CIBlock::GetGroupPermissions($IBLOCK_ID);
                $arFields["NAME"] = $arFields["NAME"]."_new";
                unset($arFields["ID"]);
                if($_REQUEST["TYPE"]!="empty")
                    $arFields["IBLOCK_TYPE_ID"]=$_REQUEST["TYPE"];
                $ID = $ib->Add($arFields);
                if(intval($ID)<=0)
                    $bError = true;
                if($_REQUEST["ID"]!="empty")
                    $iblock_prop=intval($_REQUEST["ID"]);
                else
                    $iblock_prop=$IBLOCK_ID;

                $iblock_prop_new = $ID;
                $ibp = new CIBlockProperty;
                $properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$iblock_prop));
                while ($prop_fields = $properties->GetNext()){
                    if($prop_fields["PROPERTY_TYPE"] == "L"){
                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"),
                            Array("IBLOCK_ID"=>$iblock_prop, "CODE"=>$prop_fields["CODE"]));
                        while($enum_fields = $property_enums->GetNext()){
                            $prop_fields["VALUES"][] = Array(
                                "VALUE" => $enum_fields["VALUE"],
                                "DEF" => $enum_fields["DEF"],
                                "SORT" => $enum_fields["SORT"]
                            );
                        }
                    }
                    $prop_fields["IBLOCK_ID"]=$iblock_prop_new;
                    unset($prop_fields["ID"]);
                    foreach($prop_fields as $k=>$v){
                        if(!is_array($v))$prop_fields[$k]=trim($v);
                        if($k{0}=='~') unset($prop_fields[$k]);
                    }
                    $PropID = $ibp->Add($prop_fields);
                    if(intval($PropID)<=0)
                        $bError = true;
                }

                if($_REQUEST["W_COPY"] == 'SE' OR $_REQUEST["W_COPY"] == 'EL') {

                    $arResult = array();
                    $arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y');
                    $arSelect = array('ID', 'DEPTH_LEVEL', 'SORT', 'CODE', 'NAME', 'DESCRIPTION');
                    $rsSection = CIBlockSection::GetTreeList($arFilter, $arSelect);
                    while ($arSection = $rsSection->Fetch()) {
                        $arResult[] = $arSection;
                        $arResultLevel[] = $arSection['DEPTH_LEVEL'];
                    }

                    $level = max($arResultLevel);
                    foreach ($arResult as $key => $value) {

                        $value['IBLOCK_ID'] = $ID;

                        for ($i = 1; $i <= $level; $i++) {
                            if ($value['DEPTH_LEVEL'] == $i) {
                                $s = $i - 1;
                                $value['IBLOCK_SECTION_ID'] = $sect[$s];
                                $bs = new CIBlockSection;
                                $ID_NEW_SECT = $bs->Add($value);
                                $sect[$i] = $ID_NEW_SECT;

                            }
                        }
                        $addElements[$value['ID']] = $ID_NEW_SECT;
                    }

                }

                if($_REQUEST["W_COPY"] == 'EL') {
                    if (empty($addElements)) {
                        self::addElement($IBLOCK_ID, $ID, false, false);
                    } else {
                        foreach ($addElements as $id_old => $id_new) {
                            self::addElement($IBLOCK_ID, $ID, $id_old, $id_new);
                        }
                    }
                }

                if(!$bError && $IBLOCK_ID>0)
                    echo '<div style="text-align:center;"><p style="font-size: 16px">'. GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY_END') .'</p><a style="font-size: 20px" href="iblock_edit.php?type='.$arFields['IBLOCK_TYPE_ID'].'&lang='.LANG.'&ID='.$ID.'&admin=Y">'. GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_GO_TO_IB') .'</a></div>';
                else
                    echo $bError;
            }
              die();
        }
    }

    public function addElement($old_ib,$new_ib,$old_sect,$new_sect){

        $arSelect = Array("ID", "IBLOCK_ID","SORT", "NAME","PREVIEW_TEXT","DETAIL_TEXT", "DATE_ACTIVE_FROM","CODE", "PROPERTY_*");
        $arFilter = Array("IBLOCK_ID" => $old_ib,"SECTION_ID" => $old_sect, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arProps = $ob->GetProperties();

            $el = new CIBlockElement;

            $arLoadProductArray = Array(
                // "MODIFIED_BY"    => $USER->GetID(),
                "IBLOCK_SECTION_ID" => $new_sect,
                "IBLOCK_ID"      => $new_ib,
                //   "PROPERTY_VALUES"=> $arProps,
                "SORT"           => $arFields['SORT'],
                "NAME"           => $arFields['NAME'],
                "CODE"           => $arFields['CODE'],
                "ACTIVE"         => "Y",            // активен
                "PREVIEW_TEXT"   => $arFields['PREVIEW_TEXT'],
                "DETAIL_TEXT"    => $arFields['DETAIL_TEXT']
            );
           $PRODUCT_ID = $el->Add($arLoadProductArray);

        }

    }

    public static function htmlspecialchars($string, $flags=ENT_COMPAT)
    {
        return $string;
    }



}