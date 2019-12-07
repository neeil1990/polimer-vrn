<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arFields = \SotbitSchema::getArFields();
//-----------------------------------------------------------------------------------------------------------------------------------------------
if(isset($_POST['key']) || isset($_POST['entity']))
    $key = $_POST['key'] == 'ENTITIES' ? $_POST['entity'] : $_POST['key'];
if(!isset($key) || empty($key)) {
    if(isset($arFields["ENTITIES"]) && !empty($arFields["ENTITIES"]))
    {
        $key = strtolower($arFields["ENTITIES"]["@type"]);
        $arFields = $arFields["ENTITIES"];
    }
    else
        $key = strtolower($arFields["@type"]);
}

$aTabs = array(
    array(
        "DIV" => $_POST['entity'],
        "ICON" => "main_user_edit",
    ),);
$langPrefix = 'SOTBIT_SCHEMA_POSTALADDRESS_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="PostalAddress"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField("addressLocality", GetMessage($langPrefix.'addressLocality'), false, false,
    ( isset($arFields["addressLocality"]) & !empty($arFields["addressLocality"]) ? $arFields["addressLocality"] : "" ));
$tabControl->AddEditField("addressRegion", GetMessage($langPrefix.'addressRegion'), false, false,
    ( isset($arFields["addressRegion"]) & !empty($arFields["addressRegion"]) ? $arFields["addressRegion"] : "" ));
$tabControl->AddEditField("streetAddress", GetMessage($langPrefix.'streetAddress'), false, false,
    ( isset($arFields["streetAddress"]) & !empty($arFields["streetAddress"]) ? $arFields["streetAddress"] : "" ));
$tabControl->AddEditField("postOfficeBoxNumber", GetMessage($langPrefix.'postOfficeBoxNumber'), false, false,
    ( isset($arFields["postOfficeBoxNumber"]) & !empty($arFields["postOfficeBoxNumber"]) ? $arFields["postOfficeBoxNumber"] : "" ));
$tabControl->AddEditField("addressCountry", GetMessage($langPrefix.'addressCountry'), false, false,
    ( isset($arFields["addressCountry"]) & !empty($arFields["addressCountry"]) ? $arFields["addressCountry"] : "" ));
$tabControl->AddEditField("postalCode", GetMessage($langPrefix.'postalCode'), false, false,
    ( isset($arFields["postalCode"]) & !empty($arFields["postalCode"]) ? $arFields["postalCode"] : "" ));


require (dirname(__FILE__).'/blocks/contactpoint.php');
$tabControl->Show();
?>