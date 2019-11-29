<?
use Bitrix\Main\Loader;
use Sotbit\Seometa\SeometaUrlTable;
use Sotbit\Seometa\SectionUrlTable;

Loader::includeModule('iblock');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
    die(); 
global $APPLICATION, $DB;   
if ((false == defined('B_ADMIN_SUBCHPU')) || (1 != B_ADMIN_SUBCHPU))
    return '';
if (false == defined('B_ADMIN_SUBCHPU_LIST'))
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
$strSubElementAjaxPath = '/bitrix/admin/seometa_subchpu_admin.php?lang='.LANGUAGE_ID.'&ID='.intval($_REQUEST['ID']);
$strSubElementAjaxPath = trim($strSubElementAjaxPath);
IncludeModuleLangFile(__FILE__);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');
$sTableID = "b_sotbit_seometa_chpu";
$arHideFields = array('ID');   
$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, false);

/*
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    return count($lAdmin->arFilterErrors)==0;
}
*/

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
    $parentID = $_REQUEST["parent"];
}

if(isset($parentID) && $parentID>0)
    $ParentUrl='&section='.$parentID;
else
    $ParentUrl='';                             

$arFilterChpu["CATEGORY_ID"] = intval($parentID);
$arFilterChpu['CONDITION_ID'] = $ID;

$rsSection = SectionUrlTable::getList(array(
            'limit' =>null,
            'offset' => null,
            'select' => array("*"),
            "filter" => array("PARENT_CATEGORY_ID" => $arFilterChpu["CATEGORY_ID"]),
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
        $TYPE = substr($ID, 0, 1);
        $ID = intval(substr($ID,1));
        
        if ($ID <= 0 || !$lAdmin->IsUpdated($ID))
            continue;
        if($TYPE=="P"){
            $DB->StartTransaction();
            if (!SeometaUrlTable::Update($ID, $arFields)){
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
            if (!SectionUrlTable::Update($ID, $arFields)){
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
        $dbResultList = SeometaUrlTable::GetList(
            array($by => $order),
            array(),
            false,
            false,
            array("ID")
        );
        while ($arResult = $dbResultList->Fetch()){ 
            $arID[] = "P".$arResult['ID'];             
        }
            
        $rsSection = SectionUrlTable::getList(array(
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
        $TYPE = substr($ID, 0, 1);
        $ID = intval(substr($ID,1));
        if (strlen($ID) <= 0)
            continue;

        switch ($_REQUEST['action'])
        {
            case "delete":
                @set_time_limit(0);
                if($TYPE=="P")
                {
                $DB->StartTransaction();
                if (!SeometaUrlTable::Delete($ID))
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
                    $result=SectionUrlTable::delete($ID);
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
                    if (!SeometaUrlTable::Update($ID, $arFields)) {
                        if ($ex = $APPLICATION->GetException())
                            $lAdmin->AddGroupError($ex->GetString(), $ID);
                        else
                            $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")), $ID);
                    }
                } else {
                    $result=SectionUrlTable::update($ID, array(
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
            //"sort"    =>"ID",
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
            "id"    =>"ACTIVE",
            "content"  =>GetMessage("SEO_META_TABLE_ACTIVE"),   
            "default"  =>true,
        ),
        array(  
            "id"    =>"DATE_CHANGE",
            "content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),   
            "default"  =>true,
        ),
        array(  
            "id"    =>"REAL_URL",
            "content"  =>GetMessage("SEO_META_TABLE_REAL_URL"), 
            "default"  =>true,
        ),
        array(  
            "id"    =>"NEW_URL",
            "content"  =>GetMessage("SEO_META_TABLE_NEW_URL"),    
            "default"  =>true,     
        ),
        array(  
            "id"    =>"iblock_id",
            "content"  =>GetMessage("SEO_META_TABLE_IBLOCK_ID"),
            "sort"    =>"iblock_id",
            "default"  =>true,
        ),    
        array(  
            "id"    =>"section_id",
            "content"  =>GetMessage("SEO_META_TABLE_SECTION_ID"),
            "sort"    =>"section_id",
            "default"  =>true,
        ),    
        array(  
            "id"    =>"PRODUCT_COUNT",
            "content"  =>GetMessage("SEO_META_TABLE_PRODUCT_COUNT"),
            "sort"    =>"PRODUCT_COUNT",
            "default"  =>true,
        ),
        array(  
            "id"    =>"PROPERTIES",
            "content"  =>GetMessage("SEO_META_TABLE_PROPERTIES"),
            "default"  =>true,
        ),
));
$rsData = SeometaUrlTable::getList(array(
    'select' => array('ID','NAME','ACTIVE','DATE_CHANGE','REAL_URL','NEW_URL', 'iblock_id', 'section_id', 'PRODUCT_COUNT', 'PROPERTIES'),
    'filter' =>array(
        $arFilterChpu,
    ),
    'order' => array(),
));

while($arRes = $rsData->Fetch())
{
    $arRes["T"] = "P";
    //$arRes['REAL_URL'] = str_replace(' ', '+', urldecode($arRes['REAL_URL']));
    //$arRes['REAL_URL'] = urldecode($arRes['REAL_URL']);
    $arRes['REAL_URL'] = str_replace(' ', '%20', rawurldecode($arRes['REAL_URL']));
    $arResult[] = $arRes;
}

$rsData = new CAdminSubResult($arResult, $sTableID, $lAdmin->GetListUrl(true));
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
while($arRes = $rsData->NavNext(true, ""))
{
    $row =& $lAdmin->AddRow($arRes['T'].$arRes['ID'], $arRes);
    $row->AddInputField("NAME", array("size"=>20));    
    $row->AddCheckField("ACTIVE");
    $arActions = Array();

    if($arRes['T']=='P')
    {
        $props = unserialize($arRes['PROPERTIES']);
        $pr = '';
        if(is_array($props))
        foreach($props as $code => $value){        
        $lastCode = ''; 
        if($code == 'FILTER')
            {
                $lastCode = $code;
                $keys = array_keys($value);
                $code = $keys[0];
                unset($keys);
            }
            
            $name = CIBlockProperty::GetByID($code)->fetch();
            
            if(empty($name['NAME']))
            {
                $pr .= $code.' - '.implode(', ', $value).'; ';
            }
            else
                if($lastCode == 'FILTER')
                {
                    $keys = array_keys(current($value));                
                    $pr .= 'FILTER_'.$name['NAME'].' - '.((in_array('FROM', $keys))?'FROM':$keys[0]).'-'.implode(', ', current($value)).'; ';
                }
                else
                    $pr .= $name['NAME'].' - '.implode(', ', $value).'; ';
        }
        $row->AddViewField("PROPERTIES", $pr);   
        $iblock = CIBlock::GetByID($arRes['iblock_id'])->fetch();      
        $section = CIBlockSection::GetByID($arRes['section_id'])->fetch();
        $row->AddViewField("iblock_id", '<a target="_blank" href="iblock_edit.php?type='.$iblock['IBLOCK_TYPE_ID'].'&lang='.LANG.'&ID='.$arRes['iblock_id'].'&admin=Y">'.$iblock['NAME'].'</a>');
        $row->AddViewField("section_id", '<a target="_blank" href="iblock_section_edit.php?IBLOCK_ID='.$arRes['iblock_id'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$arRes['section_id'].'&lang='.LANG.'&find_section_section='.$section['IBLOCK_SECTION_ID'].'">'.$section['NAME'].'</a>');                          
        $row->AddViewField("NAME", '<a href=\'sotbit.seometa_chpu_edit.php?ID='.$arRes['ID'].'&lang='.LANG.$ParentUrl.'\'>'.$arRes['NAME'].'</a>');   
        $arActions[] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>GetMessage("SEO_META_EDIT"),
                "ACTION"=>$lAdmin->ActionRedirect('sotbit.seometa_chpu_edit.php?ID='.$arRes['ID'].'&lang='.LANG.$ParentUrl.'">'.$arRes['NAME'].'</a>'),  
        );
    }
    else
    {
        $row->AddViewField("NAME", '<a href="sotbit.seometa_edit.php?ID='.$_REQUEST['ID'].'&parent='.$arRes['ID'].'&tabControl_active_tab=edit4&lang='.LANG.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$arRes['NAME'].'</span></a>');
        $arActions[] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>GetMessage("SEO_META_EDIT"),
                "ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_section_chpu_edit.php?parent=".$arRes['ID'])
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
    "LINK"=>"sotbit.seometa_chpu_edit.php?&lang=".LANG.$ParentUrl,
    "TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
    "ICON"=>"btn_new",
));               
   
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
$lAdmin->DisplayList();