<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$langPrefix = 'SOTBIT_SCHEMA_';
if(!Loader::includeModule('sotbit.schemaorg'))
    die;
if(isset($_REQUEST['refresh'])) {
    LocalRedirect("/bitrix/admin/sotbit.schema_settings.php?ENTITY_ID=".$_REQUEST['ENTITY_ID']."&lang=".LANG);
}
$errors = array();
//-----------------------------------------------------------------------------------------------------------------------------------------------
//  save options
if(isset($_REQUEST['save']) && !empty($_REQUEST['save']) || (isset($_REQUEST['apply']) && !empty($_REQUEST['apply']))) {
    $save = true;


    if(isset($_POST["EXCEPTION_URL"]) && !empty(implode("", $_POST["EXCEPTION_URL"])))
    {
        $arException = $_POST["EXCEPTION_URL"];
        foreach ($arException as $k => $item) {
            if(ctype_space($item) || empty($item))
                unset($arException[$k]);
        }

        if(is_array($arException)){

            foreach ($arException as $item) {
                if(preg_match('/^\/[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*\/*[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*$/',
                        $item,
                        $res) == 1){
                    $result[] = $res[0];
                }else{
                    if(count($errors) > 0)
                        $errors[] = $item;
                    else
                        $errors[] = Loc::GetMessage('EXCEPTION_ERROR_LINK').' <br>'.$item;
                }
            }
            if($result == '' || $errors == '')
                $errors[] = Loc::GetMessage('EXCEPTION_ERROR_LINK');
            else{
                $arException = serialize($result);
                Bitrix\Main\Config\Option::set(SotbitSchema::MODULE_ID, 'SCHEMA_EXCEPTIONS', $arException, $_REQUEST['SITE_ID']);
            }
        }else{
            $errors[] = Loc::GetMessage('EXCEPTION_ERROR_LINK');
        }
    }
    else
    {
        Bitrix\Main\Config\Option::set(SotbitSchema::MODULE_ID, 'SCHEMA_EXCEPTIONS', "", $_REQUEST['SITE_ID']);
    }
    SchemaSettings::getInstance()->refreshOptions();
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
//-----------------------------------------------------------------------------------------------------------------------------------------------
// if necessary that show message error or success
if($save) {
    if(!empty($errors)) {
        CAdminMessage::ShowMessage(implode('<br>', $errors));
    }
    else
        CAdminMessage::ShowMessage(array(
            "MESSAGE" => Loc::getMessage("SOTBIT_SCHEMA_SUCCESS_SAVE"),
            "TYPE" => "OK"
        ));
}
if(!$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('edit_other_settings'))
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
Loc::loadMessages(__FILE__);
//-----------------------------------------------------------------------------------------------------------------------------------------------
// get lists and other data for showing settings
if(!empty($_POST))
    $arFields = $_POST;
else
    $arFields["EXCEPTION_URL"] = SchemaSettings::getInstance($_REQUEST['SITE_ID'])->getOptions();
//-----------------------------------------------------------------------------------------------------------------------------------------------
CJSCore::Init(array("jquery"));
?>
    <link rel="stylesheet" href="/bitrix/css/sotbit.schemaorg/style.css">
    <script type="text/javascript" src="/bitrix/js/sotbit.schemaorg/script.js"></script>
<?
$aTabs = array(
    array(
        "DIV" => "general_tab",
        "TAB" => Loc::getMessage("SOTBIT_SCHEMA_GENERAL_EDIT"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SOTBIT_SCHEMA_GENERAL_TITLE")
    ),
);
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->Begin();
$tabControl->BeginNextFormTab();
$tabControl->AddSection("EXCEPTIONS_URL", GetMessage($langPrefix."EXCEPTIONS_URL"));
$tabControl->BeginCustomField("EXCEPTION_RULE", '', false);
?>
    <div class="adm-info-message-wrap" style="position: relative; top: -15px;">
        <div class="adm-info-message">
            <?=Loc::GetMessage('SIMPLE_RULE_EXCEPTION')?>
        </div>
    </div>
<?
$tabControl->EndCustomField("EXCEPTION_RULE");
$tabControl->BeginCustomField("EXCEPTION_URL", GetMessage($langPrefix."EXCEPTION_URL"), false);
if(isset($arFields['EXCEPTION_URL']) && is_array($arFields['EXCEPTION_URL']))
    foreach($arFields['EXCEPTION_URL'] as $index => $url) {
        ?>
        <tr class="adm-detail-file-row">
            <td><? echo $tabControl->GetCustomLabelHTML() ?></td>
            <td>
                <input type="text" class="exceptions" name="EXCEPTION_URL[]" value="<?=$url;?>">
            </td>
        </tr>
        <?
    }
$tabControl->EndCustomField("EXCEPTION_URL");
$tabControl->addEditField("EXCEPTION_URL[]", "", false);
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