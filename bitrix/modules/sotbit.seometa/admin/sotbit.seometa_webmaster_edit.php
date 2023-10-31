<?

use Sotbit\Seometa\Orm\SeometaUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
global $APPLICATION;

$moduleId = 'sotbit.seometa';
if (!Loader::includeModule('iblock') || !Loader::includeModule($moduleId)) {
    die();
}
Loc::loadMessages(__FILE__);

// For menu
CJSCore::Init(array(
    "jquery"
));

$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

IncludeModuleLangFile(__FILE__);

$aTabs = array(
    array(
        "DIV" => "edit1",
        "TAB" => loc::getMessage("SEO_META_EDIT_TAB_URL"),
        "ICON" => "main_user_edit",
        "TITLE" => loc::getMessage("SEO_META_EDIT_TAB_URL")
    ),
);

$tabControl = new CAdminForm("tabControl",
    $aTabs);

$ID = intval($_REQUEST['ID']);

if ($ID > 0) {
    $condition = SeometaUrlTable::getById($ID);
}

$APPLICATION->SetTitle(($ID > 0 ? loc::getMessage("SEO_META_EDIT_EDIT") . $ID . ' "' . $condition["NAME"] . '"' : ''));

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

$aMenu[] = array(
    "TEXT" => loc::getMessage("SEO_META_EDIT_BACK"),
    "TITLE" => loc::getMessage("SEO_META_EDIT_BACK_TITLE"),
    "LINK" => "sotbit.seometa_webmaster_list.php?lang=" . LANG,
    "ICON" => "btn_list"
);

$context = new CAdminContextMenu($aMenu);
$context->Show();

if (isset($errors) && is_array($errors) && count($errors) > 0) {
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => $errors[0]
    ));
}

$tabControl->Begin(array(
    "FORM_ACTION" => $APPLICATION->GetCurPage()
));

$tabControl->BeginNextFormTab();

$tabControl->AddViewField('NAME',
    loc::getMessage("SEO_META_NAME"),
    htmlspecialcharsbx($condition['NAME']),
    false);
$tabControl->AddViewField('DATE_SCAN',
    loc::getMessage("SEO_META_DATE_SCAN"),
    $condition['DATE_SCAN'],
    false);
$tabControl->AddViewField('STATUS',
    loc::getMessage("SEO_META_STATUS"),
    $condition['STATUS'],
    false);
$tabControl->AddViewField('DESCRIPTION',
    loc::getMessage("SEO_META_DESCRIPTION"),
    $condition['DESCRIPTION'],
    false);
$tabControl->AddViewField('KEYWORDS',
    loc::getMessage("SEO_META_KEYWORDS"),
    $condition['KEYWORDS'],
    false);
$tabControl->AddViewField('TITLE',
    loc::getMessage("SEO_META_TITLE"),
    $condition['TITLE'],
    false);

$tabControl->Show();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>