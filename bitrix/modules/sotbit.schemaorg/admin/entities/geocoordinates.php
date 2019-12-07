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
$langPrefix = 'SOTBIT_SCHEMA_GEOCOORDINATES_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="GeoCoordinates"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "address",
    array(
        '' => '...',
//        'text' => 'Text',
        'postaladdress' => 'PostalAddress',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[addressCountry]" : "addressCountry", GetMessage($langPrefix.'addressCountry'), false, false,
    ( isset($arFields["addressCountry"]) & !empty($arFields["addressCountry"]) ? $arFields["addressCountry"] : "" ) );
$tabControl->AddEditField($key ? $key."[elevation]" : "elevation", GetMessage($langPrefix.'elevation'), false, false,
    ( isset($arFields["elevation"]) & !empty($arFields["elevation"]) ? $arFields["elevation"] : "" ) );
$tabControl->AddEditField($key ? $key."[latitude]" : "latitude", GetMessage($langPrefix.'latitude'), false, false,
    ( isset($arFields["latitude"]) & !empty($arFields["latitude"]) ? $arFields["latitude"] : "" ) );
$tabControl->AddEditField($key ? $key."[longitude]" : "longitude", GetMessage($langPrefix.'longitude'), false, false,
    ( isset($arFields["longitude"]) & !empty($arFields["longitude"]) ? $arFields["longitude"] : "" ) );
$tabControl->AddEditField($key ? $key."[postalCode]" : "postalCode", GetMessage($langPrefix.'postalCode'), false, false,
    ( isset($arFields["postalCode"]) & !empty($arFields["postalCode"]) ? $arFields["postalCode"] : "" ) );

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>