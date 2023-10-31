<?php

use Bitrix\Main\Loader;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SitemapSectionTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
    die(); 
global $APPLICATION, $DB;
if ((false == defined('B_ADMIN_SUBCONDITIONS')) || (1 != B_ADMIN_SUBCONDITIONS))
    return '';
if (false == defined('B_ADMIN_SUBCONDITIONS_LIST'))
    return '';
$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if($POST_RIGHT=="D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));    

$CCSeoMeta= new CCSeoMeta();
if(!$CCSeoMeta->getDemo())
    return '';                                  
$id_module='sotbit.seometa';
Loader::includeModule($id_module);
if ($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame')
    CFile::DisableJSFunction(true);
$strSubElementAjaxPath = '/bitrix/admin/seometa_suburl_admin.php?lang='.LANGUAGE_ID.'&ID='.intval($_REQUEST['ID']);
$strSubElementAjaxPath = trim($strSubElementAjaxPath);
IncludeModuleLangFile(__FILE__);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');
$sTableID = "b_sotbit_seometa";
$arHideFields = array('ID');   
$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, false);    
                                                 
 /*   
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    return count($lAdmin->arFilterErrors)==0;
}   */    

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
    $parentID = $_REQUEST["parent"];
}


if(isset($parentID) && $parentID>0)
    $ParentUrl='&section='.$parentID;
else
    $ParentUrl='';                             

$arFilter["CATEGORY_ID"] = intval($parentID);

$rsSection = SitemapSectionTable::getList(array(
            'limit' =>null,
            'offset' => null,
            'select' => array("*"),
            "filter" => array("PARENT_CATEGORY_ID" => $arFilter["CATEGORY_ID"]),
));    
while($arSection = $rsSection->Fetch())
{
    $arSection["T"]="S";
    $arResult[]=$arSection;
}     
unset($rsSection);
   
if ($lAdmin->EditAction())
{
    foreach ($_POST['FIELDS'] as $ID => $arFields)
    {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));
        
        if ($ID <= 0 || !$lAdmin->IsUpdated($ID))
            continue;
        if($TYPE=="P"){
            $DB->StartTransaction();
            if (!ConditionTable::Update($ID, $arFields)){
                if ($ex = $APPLICATION->GetException())
                    $lAdmin->AddUpdateError($ex->GetString(), $ID);
                else
                    $lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")), $ID);
                $DB->Rollback();
            } else {
                $DB->Commit();
            }
        } else {             
            $DB->StartTransaction();        
            if (!SitemapSectionTable::Update($ID, $arFields)){
                if ($ex = $APPLICATION->GetException())
                    $lAdmin->AddUpdateError($ex->GetString(), $ID);
                else
                    $lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")), $ID);
                $DB->Rollback();
            } else {
                $DB->Commit();
            }
        }
    }
}
if ($arID = $lAdmin->GroupAction())
{
    if ($_REQUEST['action_target']=='selected')
    { 
        $arID = array();
        $dbResultList = ConditionTable::GetList(
            array($by => $order),
            array(),
            false,
            false,
            array("ID")
        );
        while ($arResult = $dbResultList->Fetch()){ 
            $arID[] = "P".$arResult['ID'];             
        }
            
        $rsSection = SitemapSectionTable::getList(array(
            'limit' =>null,
            'offset' => null,
            'select' => array("*"),
            "filter" => $filter
        ));
        while($arSection = $rsSection->Fetch())
        {    
            $arSection["T"]="S";                  
            $arSection['ID']="S".$arSection['ID'];
            $arID[]=$arSection;
        }
    }

    foreach ($arID as $ID)
    {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));
        if (mb_strlen($ID) <= 0)
            continue;

        switch ($_REQUEST['action'])
        {
            case "delete":
                @set_time_limit(0);
                if($TYPE=="P")
                {
                $DB->StartTransaction();
                if (!ConditionTable::Delete($ID))
                {
                    $DB->Rollback();

                    if ($ex = $APPLICATION->GetException())
                        $lAdmin->AddGroupError($ex->GetString(), $ID);
                    else
                        $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SEO_META_DEL_ERROR")), $ID);
                }
                else
                {
                    $DB->Commit();
                }
                } else {
                    $result=SitemapSectionTable::delete($ID);
                    if(!$result->isSuccess())
                    {
                        $lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
                    }
                }
                break;
            case "activate":
            case "deactivate":
                $arFields = array(
                    "ACTIVE" => (($_REQUEST['action']=="activate") ? "Y" : "N")
                );
                if($TYPE=="P") {
                    if (!ConditionTable::Update($ID, $arFields)) {
                        if ($ex = $APPLICATION->GetException())
                            $lAdmin->AddGroupError($ex->GetString(), $ID);
                        else
                            $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")), $ID);
                    }
                } else {
                    $result=SitemapSectionTable::update($ID, array(
                            'ACTIVE' => $arFields["ACTIVE"],
                        ));
                        if (!$result->isSuccess())
                            $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
                }
                break;
        }
    }
}
                        
$lAdmin->AddHeaders(array(
        array(  
            "id"    =>"ID",
            "content"  =>GetMessage("SEO_META_TABLE_ID"),
            "sort"    =>"ID",
            "align"    =>"right",
            "default"  =>true,
        ),
        array(  
            "id"    =>"NAME",
            "content"  =>GetMessage("SEO_META_TABLE_TITLE"),
            //"sort"    =>"NAME",
            "default"  =>true,
        ),
        array(  
            "id"    =>"SORT",
            "content"  =>GetMessage("SEO_META_TABLE_SORT"),
            //"sort"    =>"SORT",
            "align"    =>"right",
            "default"  =>true,
        ),
        array(  
            "id"    =>"ACTIVE",
            "content"  =>GetMessage("SEO_META_TABLE_ACTIVE"),
            //"sort"    =>"ACTIVE",
            "default"  =>true,
        ),
        array(  
            "id"    =>"DATE_CHANGE",
            "content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),
            //"sort"    =>"DATE_CHANGE",
            "default"  =>true,
        ),
));     
$rsData = ConditionTable::getList(array(
    'select' => array('ID','NAME','SORT','ACTIVE','DATE_CHANGE','SECTIONS'),
    'filter' =>array(
        $arFilter,
    ),
    'order' => array(),
));    
while($arRes = $rsData->Fetch()) {  
    $sections = unserialize($arRes['SECTIONS']);    
    if(empty($sections) || !in_array(intval($_REQUEST['ID']),$sections))
        continue; 
    $arRes["T"]="P";                   
    $arResult[] = $arRes;
}             
$rsData = new CAdminSubResult($arResult, $sTableID, $lAdmin->GetListUrl(true));  
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));  
while($arRes = $rsData->NavNext(false, "")){
    $row =& $lAdmin->AddRow($arRes['T'].$arRes['ID'], $arRes);
    $row->AddInputField("NAME", array("size"=>20));
    $row->AddInputField("SORT", array("size"=>20));
    $row->AddCheckField("ACTIVE");
    $arActions = Array();    
if($arRes['T']=='P')
{                             
    $row->AddViewField("NAME", '<a href=\'sotbit.seometa_edit.php?TYPE_OF_INFOBLOCK='.$_REQUEST['type'].'&INFOBLOCK='.intval($_REQUEST['IBLOCK_ID']).'&SECTIONS='.$arRes['SECTIONS'].'&FROM='.intval($_REQUEST['ID']).'&ID='.$arRes['ID'].'&SECT_FROM='.$_REQUEST['find_section_section'].'&lang='.LANG.$ParentUrl.'\'>'.$arRes['NAME'].'</a>');   
    $arActions[] = array(
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>GetMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect('sotbit.seometa_edit.php?ITYPE_OF_INFOBLOCK='.$_REQUEST['type'].'&INFOBLOCK='.intval($_REQUEST['IBLOCK_ID']).'&SECTIONS='.$arRes['SECTIONS'].'&FROM='.intval($_REQUEST['ID']).'&ID='.$arRes['ID'].'&SECT_FROM='.$_REQUEST['find_section_section'].'&lang='.LANG.$ParentUrl.'">'.$arRes['NAME'].'</a>'),  
    );
}
else
{
    $row->AddViewField("NAME", '<a href="iblock_section_edit.php?IBLOCK_ID='.$_REQUEST['IBLOCK_ID'].'&type='.$_REQUEST['type'].'&ID='.$_REQUEST['ID'].'&find_section_section='.$_REQUEST['find_section_section'].'&form_section_4_active_tab=seometa_url-mode&parent='.$arRes['ID'].'&lang='.LANG.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$arRes['NAME'].'</span></a>');
    $arActions[] = array(
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>GetMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_section_edit.php?parent=".$arRes['ID'])
    );
}   

    $arActions[] = array("SEPARATOR"=>true);
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);

    $row->AddActions($arActions);       
}    

if (isset($row))
    unset($row);

$lAdmin->AddFooter(array(
    array("title"=>GetMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
    array("counter"=>true, "title"=>GetMessage("SEO_META_LIST_CHECKED"), "value"=>"0"),
));   
$lAdmin->AddGroupActionTable(Array(
    "delete"=>GetMessage("SEO_META_LIST_DELETE"),
    //"copy"=>GetMessage("SEO_META_LIST_COPY"),
    "activate"=>GetMessage("SEO_META_LIST_ACTIVATE"),
    "deactivate"=>GetMessage("SEO_META_LIST_DEACTIVATE"),
));                                                                      
$aContext = array(array(
    "TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
    "LINK"=>"sotbit.seometa_edit.php?TYPE_OF_INFOBLOCK=".$_REQUEST['type']."&INFOBLOCK=".intval($_REQUEST['IBLOCK_ID'])."&SECTIONS=".serialize(array(intval($_REQUEST['ID'])))."&SECT_FROM=".$_REQUEST['find_section_section']."&FROM=".intval($_REQUEST['ID'])."&lang=".LANG.$ParentUrl,
    "TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
    "ICON"=>"btn_new",
));               
   
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
$lAdmin->DisplayList();