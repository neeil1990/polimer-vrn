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
$langPrefix = 'SOTBIT_SCHEMA_MONETARYAMOUNT_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", getMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="MonetaryAmount"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl->AddEditField($key ? $key."[maxValue]" : "maxValue", Loc::getMessage($langPrefix.'maxValue'), false, false,
    ( isset($arFields["maxValue"]) & !empty($arFields["maxValue"]) ? $arFields["maxValue"] : "" ));
$tabControl->AddEditField($key ? $key."[minValue]" : "minValue", Loc::getMessage($langPrefix.'minValue'), false, false,
    ( isset($arFields["minValue"]) & !empty($arFields["minValue"]) ? $arFields["minValue"] : "" ));
$tabControl->AddEditField($key ? $key."[value]" : "value", Loc::getMessage($langPrefix.'value'), false, false,
    ( isset($arFields["value"]) & !empty($arFields["value"]) ? $arFields["value"] : "" ));
$tabControl->AddEditField($key ? $key."[currency]" : "currency", Loc::getMessage($langPrefix.'currency'), false, false,
    ( isset($arFields["currency"]) & !empty($arFields["currency"]) ? $arFields["currency"] : "" ));

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>