<?php

$MODULE_ID = 'prime.smartbanners';
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$MODULE_ID."/include.php");
IncludeModuleLangFile(__FILE__);

$TableID = "b_prime_smartbanners"; // ID таблицы

$oSort = new CAdminSorting($TableID, "NAME", "desc");
$itemAdmin = new CAdminList($TableID, $oSort);

$Data = new Nb_class;
$resData = $Data->GetList(array($by=>$order),false,array("ID","NAME","SHOWS","CLICKS"));

$resData = new CAdminResult($resData, $TableID);
$resData->NavStart();

$itemAdmin->AddHeaders(array(
    array("id"    =>"ID",
        "content"  =>"ID",
        "sort"     =>"id",
        "default"  =>true,
    ),
    array(  "id"    => "NAME",
        "content"   => "Название",
        "sort"      => "name",
        "default"   => true,
    ),
    array(  "id"    => "SHOWS",
        "content"   => "Показы",
        "sort"      => "shows",
        "default"   => true,
    ),
    array(  "id"    => "CLICKS",
        "content"   => "Клики",
        "sort"      => "clicks",
        "default"   => true,
    ),
));

while($res = $resData->NavNext(true, "f_"))
{
    $row =& $itemAdmin->AddRow($f_ID, $res);

    $row->AddActions($arActions);
}

$APPLICATION->SetTitle("Статистика");
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
