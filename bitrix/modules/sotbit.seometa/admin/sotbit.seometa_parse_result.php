<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

global $APPLICATION;

$id_module = 'sotbit.seometa';
CJSCore::Init(array("jquery"));
if (!Loader::includeModule('iblock') || !Loader::includeModule($id_module)) {
    die();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

if (CCSeoMeta::ReturnDemo() == 2) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("SEO_META_DEMO") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}
if (CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("SEO_META_DEMO_END") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    return '';
}


$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$fileID = $request->get('file');

$rsData = \Sotbit\Seometa\Orm\ParseResultTable::query()
    ->setSelect(['*'])
    ->setFilter(['FILE_ID' => $fileID])
    ->setOrder(['ID' => 'ASC'])
    ->exec();

while ($arRes = $rsData->fetch()) {
    $arResult[] = $arRes;
}

$APPLICATION->SetTitle($request->get('entity') === 'cond' ? Loc::getMessage($id_module . "_COND_TITLE") : Loc::getMessage($id_module . "_CHPU_TITLE"));

if ($request->isPost() && $request->get('close')) {
    if ($entity = $request->get('entity') === 'cond') {
        LocalRedirect("/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG);
    } else {
        LocalRedirect("/bitrix/admin/sotbit.seometa_chpu_list.php?lang=" . LANG);
    }
}

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage($id_module . '_IMPORT_RESULT_TITLE'),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage($id_module . '_IMPORT_RESULT_TITLE')
    ],
];

$tabControl = new CAdminForm("tabControl", $aTabs);
$entity = $request->get('entity') ? Loc::getMessage($id_module . '_COND') : Loc::getMessage($id_module . '_CHPU');
if (!$arResult) {
    CAdminMessage::ShowMessage([
        "MESSAGE" => Loc::getMessage($id_module . '_IMPORT_SUCCESS'),
        "DETAILS" => Loc::getMessage($id_module . '_IMPORT_DETAILS', ['#ENTITY#' => $entity]),
        "TYPE" => "OK",
    ]);
    $additional_html = '<form action="' . $APPLICATION->GetCurPageParam() . '" method="post"><input type="submit" style="margin-right: 10px" class="adm-btn-apply" name="close" value="' . Loc::getMessage("MAIN_CLOSE") . '" /></form>';
    echo $additional_html;
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    return '';
}
$sTableID = "b_sotbit_seometa_parse_result ";
$rs = new \CDBResult();
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$lAdmin = new CAdminList($sTableID);
if ($rsData->arResult) {
    $rsData->NavStart();
}
$lAdmin->AddHeaders([
    [
        "id" => "FILE_ID",
        "content" => Loc::getMessage($id_module . "_TABLE_FILE_ID"),
        "align" => "right",
        "default" => true,
    ],
    [
        "id" => "ENTITY_ROW",
        "content" => Loc::getMessage($id_module . "_TABLE_ENTITY_ROW"),
        "default" => true,
    ],
    [
        "id" => "ENTITY_NAME",
        "content" => Loc::getMessage($id_module . "_TABLE_ENTITY_NAME"),
        "default" => true,
    ],
    [
        "id" => "MESSAGE",
        "content" => Loc::getMessage($id_module . "_TABLE_MESSAGE"),
        "default" => true,
    ],
]);
while ($arRes = $rsData->NavNext()) {
    $row = &$lAdmin->AddRow($arRes['ID'], $arRes);
    $row->AddField("ENTITY_ROW", $arRes['ENTITY_ROW']);
    $row->AddField("FILE_ID", $arRes['FILE_ID']);
    $row->AddField("ENTITY_NAME", $arRes['ENTITY_NAME']);
    $row->AddField("MESSAGE", $arRes['MESSAGE']);

    $row->AddActions([]);
}

$lAdmin->DisplayList();
$additional_html = '<form action="' . $APPLICATION->GetCurPageParam() . '" method="post"><input type="submit" style="margin-right: 10px" class="adm-btn-apply" name="close" value="' . Loc::getMessage("MAIN_CLOSE") . '" /></form>';
echo $additional_html;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
return '';

