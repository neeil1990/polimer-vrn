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
$langPrefix = 'SOTBIT_SCHEMA_GEOSHAPE_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection($langPrefix."SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="GeoShape"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl->AddEditField($key ? $key."[box]" : "box", GetMessage($langPrefix.'box'), false, false,
    ( isset($arFields["box"]) & !empty($arFields["box"]) ? $arFields["box"] : "" ));
$tabControl->AddEditField($key ? $key."[circle]" : "circle", GetMessage($langPrefix.'circle'), false, false,
    ( isset($arFields["circle"]) & !empty($arFields["circle"]) ? $arFields["circle"] : "" ));
$tabControl->AddEditField($key ? $key."[line]" : "line", GetMessage($langPrefix.'line'), false, false,
    ( isset($arFields["line"]) & !empty($arFields["line"]) ? $arFields["line"] : "" ));
$tabControl->AddEditField($key ? $key."[polygon]" : "polygon", GetMessage($langPrefix.'polygon'), false, false,
    ( isset($arFields["polygon"]) & !empty($arFields["polygon"]) ? $arFields["polygon"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "geoMidpoint",
    array(
        '' => '...',
        'geocoordinates' => 'GeoCoordinates',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "address",
    array(
        '' => '...',
        'postaladdress' => 'PostalAddress',
        'text' => 'Text',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "addressCountry",
    array(
        '' => '...',
//        'text' => 'Text',
        'country' => 'Country',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[postalCode]" : "postalCode", GetMessage($langPrefix.'postalCode'), false, false,
    ( isset($arFields["postalCode"]) & !empty($arFields["postalCode"]) ? $arFields["postalCode"] : "" ));
$tabControl->AddEditField($key ? $key."[postalCode]" : "postalCode", GetMessage($langPrefix.'postalCode'), false, false,
    ( isset($arFields["postalCode"]) & !empty($arFields["postalCode"]) ? $arFields["postalCode"] : "" ));

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>