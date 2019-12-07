<?
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Sotbit\Schemaorg\SchemaPageMetaTable;
use Sotbit\Schemaorg\SchemaCategoryTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$langPrefix = 'SOTBIT_SCHEMA_CATEGORY_';



if(!Loader::includeModule('sotbit.schemaorg'))
    die;

if(isset($_REQUEST['refresh'])) {
    LocalRedirect("/bitrix/admin/sotbit.schema_settings.php?ENTITY_ID=".$_REQUEST['ENTITY_ID']."&lang=".LANG);
}

$errors = array();
global $APPLICATION;


//-----------------------------------------------------------------------------------------------------------------------------------------------
//  save options
if((isset($_REQUEST['save']) && !empty($_REQUEST['save']) || (isset($_REQUEST['apply']) && !empty($_REQUEST['apply'])))) {
    $obj = new SchemaCategoryTable();

    $arFields = array_filter(array_keys(SchemaCategoryTable::getmap()), function($val) {
        return $val != 'ID';
    });

    $arFields = array_intersect_key($_REQUEST, array_flip($arFields));
    if(!empty($_REQUEST['CATEGORY_ID']))
        $arFields['PARENT_ID'] = intval($_REQUEST['CATEGORY_ID']);
    $arFields['SITE_ID'] = $_REQUEST['SITE_ID'];
    $arFields['TIMESTAMP_X'] = new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );

    if(isset($_REQUEST['ID'])) {
        $result = SchemaCategoryTable::update(intval($_REQUEST['ID']), $arFields);
    } else{
        $arFields['DATE_CREATE'] = new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
        if(!isset($arFields["SORT"]) || empty($arFields["SORT"]))
            $arFields["SORT"] = 100;
        $result = SchemaCategoryTable::Add($arFields);
    }

    if($result->isSuccess()) {
        if(isset($_REQUEST['save']) && !empty($_REQUEST['save'])) {
            LocalRedirect('/bitrix/admin/sotbit.schema_url_list.php?lang='.SITE_ID.'&SITE_ID='.$_REQUEST['SITE_ID']);
        }
        else {
            $_SESSION['SAVE_SCHEMA'][$result->getId()] = true;
            LocalRedirect($APPLICATION->GetCurPageParam("ID=".$result->getId(), array("ID")));
        }
    }
    else {
        $errors = $result->getErrorMessages();
    }
}
$arFields = array();
if($_REQUEST['ID']) {
    $arFields = SchemaCategoryTable::getList(array(
        'filter' => array('ID' => intval($_REQUEST['ID'])),
    ))->fetch();
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
//-----------------------------------------------------------------------------------------------------------------------------------------------
// if necessary that show message error or success
if(isset($_SESSION['SAVE_SCHEMA'][intval($_REQUEST['ID'])])) {
    unset($_SESSION['SAVE_SCHEMA'][intval($_REQUEST['ID'])]);
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => Loc::getMessage($langPrefix."SUCCESS_SAVE"),
        "TYPE" => "OK"
    ));
}
else if(!empty($errors))
    CAdminMessage::ShowMessage(implode('<br>', $errors));
if(!$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('edit_other_settings'))
    $APPLICATION->AuthForm(GetMessage($langPrefix."ACCESS_DENIED"));
Loc::loadMessages(__FILE__);
//-----------------------------------------------------------------------------------------------------------------------------------------------
CJSCore::Init(array("jquery"));
?>
    <link rel="stylesheet" href="/bitrix/css/sotbit.schemaorg/style.css">
    <script type="text/javascript" src="/bitrix/js/sotbit.schemaorg/script.js"></script>
<?

$APPLICATION->setTitle(GetMessage($langPrefix."PAGE_TITLE"));

$aTabs = array(
    array(
        "DIV" => "general_tab",
        "TAB" => Loc::getMessage($langPrefix."GENERAL_EDIT"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage($langPrefix."GENERAL_TITLE")
    ),
);

$aMenu= array(array(
                  "TEXT" => GetMessage( $langPrefix."EDIT_BACK" ),
                  "TITLE" => GetMessage( $langPrefix."EDIT_BACK_TITLE" ),
                  "ICON" => "btn_list",
                  'MENU' =>array(
                      array(
                          "TEXT" => GetMessage( $langPrefix."EDIT_LIST" ),
                          "TITLE" => GetMessage( $langPrefix."EDIT_LIST_TITLE" ),
                          "LINK" => '/bitrix/admin/sotbit.schema_url_list.php?lang='.SITE_ID.(isset($_REQUEST['SITE_ID']) ? '&SITE_ID='.$_REQUEST['SITE_ID'] : ''),
                          "ICON" => "btn_list"
                      ),
                      array(
                          "TEXT" => GetMessage( $langPrefix."EDIT_SECTION_BACK" ),
                          "TITLE" => GetMessage( $langPrefix."EDIT_SECTION_BACK_TITLE" ),
                          "LINK" => '/bitrix/admin/sotbit.schema_url_list.php?lang='.SITE_ID.(isset($_REQUEST['SITE_ID']) ? '&SITE_ID='.$_REQUEST['SITE_ID'] : '').(isset($_REQUEST['CATEGORY_ID']) ? '&CATEGORY_ID='.$_REQUEST['CATEGORY_ID'] : ''),
                      )
                  )),
);
$context = new CAdminContextMenu( $aMenu );
$context->Show();
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->Begin();
$tabControl->BeginNextFormTab();
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->AddEditField("NAME", GetMessage($langPrefix.'CATEGORY_NAME'), true, false, $arFields['NAME']);
$tabControl->AddEditField("SORT", GetMessage($langPrefix.'SORT'), false, false, $arFields['SORT']);
$tabControl->Buttons($arButtonsParams);
$tabControl->arParams["FORM_ACTION"] = $APPLICATION->GetCurPageParam();

if( SotbitSchema::returnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SOTBIT_CRM_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}

if( SotbitSchema::returnDemo() == 3 || SotbitSchema::returnDemo() == 0)
{
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SOTBIT_CRM_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

$tabControl->Show();
$tabControl->ShowWarnings($tabControl->GetName(), $message);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>