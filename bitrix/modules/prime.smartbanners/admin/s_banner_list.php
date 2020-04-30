<?php
$MODULE_ID = 'prime.smartbanners';
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$MODULE_ID."/include.php");
IncludeModuleLangFile(__FILE__);

$TableID = "b_prime_smartbanners"; // ID таблицы
$oSort = new CAdminSorting($TableID, "NAME", "desc");
$itemAdmin = new CAdminList($TableID, $oSort);

if($itemAdmin->EditAction())
{
    foreach($FIELDS as $ID=>$arFields)
    {
        $DB->StartTransaction();
        $ID = trim($ID);

        $Banner = new Nb_class;
        if(!$Banner->Update($ID, $arFields))
        {
            $itemAdmin->AddUpdateError(GetMessage("SAVE_ERROR").$ID.": ".$oBanner->LAST_ERROR, $ID);
            $DB->Rollback();
        }
        $DB->Commit();
    }
}

if(($arID = $itemAdmin->GroupAction()))
{
    if($_REQUEST['action_target']=='selected')
    {
        $Data = new Nb_class;
        $resData = $Data->GetList();
        while($arResult = $resData->Fetch())
            $arID[] = $arResult['ID'];
    }

    foreach($arID as $ID)
    {
        if(strlen($ID)<=0)
            continue;
        $ID = IntVal($ID);

        switch($_REQUEST['action'])
        {
            case "delete":
                @set_time_limit(0);
                $DB->StartTransaction();
                if(!Nb_class::Delete($ID))
                {
                    $DB->Rollback();
                    $itemAdmin->AddGroupError(GetMessage("ban_del_err"), $ID);
                }
                $DB->Commit();
                break;
            case "copy":
                if(!Nb_class::copyRow($ID))
                {
                    $DB->Rollback();
                    $itemAdmin->AddGroupError(GetMessage("ban_del_err"), $ID);
                }
                break;
            case "activate":
            case "deactivate":
                $Data = new Nb_class;
                if(($resData = $Data->GetByID($ID)) && ($arFields = $resData->Fetch()))
                {
                    $arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");
                    if(!$Data->UpdateActive($ID, $arFields))
                        $itemAdmin->AddGroupError(GetMessage("SAVE_ERROR").$Data->LAST_ERROR, $ID);
                }
                else
                    $itemAdmin->AddGroupError(GetMessage("SAVE_ERROR")." ".GetMessage("NO_BANNER"), $ID);
                break;
        }
    }
}


$Data = new Nb_class;
$resData = $Data->GetList(array($by=>$order));

$resData = new CAdminResult($resData, $TableID);
$resData->NavStart();

$itemAdmin->AddHeaders(array(
    array("id"     =>"ID",
        "content"  =>"ID",
        "sort"     =>"id",
        "default"  =>true,
    ),
    array(  "id"   =>"NAME",
        "content"  =>GetMessage("COL_NAME"),
        "sort"     =>"name",
        "default"  =>true,
    ),
    array(  "id"   =>"ACTIVE",
        "content"  =>GetMessage("COL_ACTIVE"),
        "sort"     =>"active",
        "default"  =>true,
    ),
    array(  "id"   =>"SID",
        "content"  =>GetMessage("COL_SID"),
        "sort"     =>"sid",
        "default"  =>false,
    ),
    array(  "id"   =>"SHOW_FROM",
        "content"  =>GetMessage("COL_SHOW_FROM"),
        "sort"     =>"show_form",
        "default"  =>false,
    ),
    array(  "id"   =>"SHOW_TO",
        "content"  =>GetMessage("COL_SHOW_TO"),
        "sort"     =>"show_to",
        "default"  =>false,
    ),
    array(  "id"    =>"PAUSE",
        "content"  =>GetMessage("COL_WEIGHT"),
        "sort"     =>"pause",
        "default"  =>true,
    ),
    array(  "id"    =>"URL",
        "content"  =>GetMessage("COL_URL"),
        "sort"     =>"url",
        "default"  =>false,
    ),
    array(  "id"    =>"OPEN_NEW_WINDOW",
        "content"  =>GetMessage("COL_OPEN_NEW_WINDOW"),
        "sort"     =>"open_new_window",
        "default"  =>false,
    ),
    array(  "id"    =>"SHOW_OFF",
        "content"  =>GetMessage("COL_SHOW_OFF"),
        "sort"     =>"show_off",
        "default"  =>false,
    ),
    array(  "id"    =>"SHOW_POSITION",
        "content"  =>GetMessage("COL_SHOW_POSITION"),
        "sort"     =>"show_position",
        "default"  =>false,
    ),
    array(  "id"    =>"SHOW_OUT_SITE",
        "content"  =>GetMessage("COL_SHOW_OUT_SITE"),
        "sort"     =>"show_out_site",
        "default"  =>false,
    ),
    array(  "id"    =>"SHOW_TIME",
        "content"  =>GetMessage("COL_SHOW_TIME"),
        "sort"     =>"show_time",
        "default"  =>false,
    ),
));


$arTypes = array();
$arTypesCode = array();
while($res = $resData->NavNext(true, "f_"))
{

    $row =& $itemAdmin->AddRow($f_ID, $res);

    $row->AddInputField("NAME", array("size"=>40));
    $row->AddViewField("NAME", '<a href="nb_s_banner_edit.php?ID='.$f_ID.'&lang='.LANG.'">'.$f_NAME.'</a>');
    $row->AddCheckField("ACTIVE");
    $row->AddCheckField("OPEN_NEW_WINDOW");
    $row->AddCheckField("SHOW_OUT_SITE");
    $row->AddCheckField("SHOW_TIME");

    $row->AddViewField("SID",htmlspecialcharsbx(implode("\n",array_keys(unserialize(htmlspecialchars_decode($f_SID))))));
    $row->AddViewField("SHOW_OFF",htmlspecialcharsbx(implode("\n",unserialize(htmlspecialchars_decode($f_SHOW_OFF)))));
    $row->AddViewField("PAUSE",$f_PAUSE);

    // сформируем контекстное меню
    $arActions = Array();

    // редактирование элемента
    $arActions[] = array(
        "ICON"=>"edit",
        "DEFAULT"=>true,
        "TEXT"=>GetMessage("REK_EDIT"),
        "ACTION"=>$itemAdmin->ActionRedirect("nb_s_banner_edit.php?ID=".$f_ID)
    );

    $arActions[] = array("SEPARATOR"=>true);

    $arActions[] = array(
        "ICON" =>  "copy",
        "TEXT" =>  GetMessage("COPY"),
        "ACTION"=> $itemAdmin->ActionDoGroup($f_ID, "copy")
    );

    $arActions[] = array("SEPARATOR"=>true);

    $arActions[] = array(
        "ICON" => "delete",
        "TEXT" => GetMessage("REK_DEL"),
        "ACTION" => "if(confirm('".GetMessage('REK_DEL_CONF')."')) ".$itemAdmin->ActionDoGroup($f_ID, "delete")
    );

    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);
    $row->AddActions($arActions);
}

$itemAdmin->AddGroupActionTable(Array(
    "delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"), // удалить выбранные элементы
    "activate"=>GetMessage("MAIN_ADMIN_LIST_ACTIVATE"), // активировать выбранные элементы
    "deactivate"=>GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"), // деактивировать выбранные элементы
));

$addbtn = array(
    array(
        "TEXT" => GetMessage("BANNER_ADD"),
        "LINK"=>"nb_s_banner_edit.php?lang=".LANG,
        "TITLE"=>GetMessage("BANNER_ADD_TITLE"),
        "ICON"=>"btn_new",
    ),
);
$itemAdmin->AddAdminContextMenu($addbtn);

$itemAdmin->CheckListMode();
$APPLICATION->SetTitle(GetMessage("BANNER_LIST_TITLE"));
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?
$itemAdmin->DisplayList();
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>

