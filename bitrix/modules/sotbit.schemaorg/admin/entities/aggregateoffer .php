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
$langPrefix = 'SOTBIT_SCHEMA_AGGREGATEOFFER_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="AggregateOffer"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl->AddEditField($key ? $key."[highPrice]" : "highPrice", GetMessage($langPrefix.'highPrice'), false, false,
    ( isset($arFields["highPrice"]) & !empty($arFields["highPrice"]) ? $arFields["highPrice"] : "" ));
$tabControl->AddEditField($key ? $key."[lowPrice]" : "lowPrice", GetMessage($langPrefix.'lowPrice'), false, false,
    ( isset($arFields["lowPrice"]) & !empty($arFields["lowPrice"]) ? $arFields["lowPrice"] : "" ));
$tabControl->AddEditField($key ? $key."[offerCount]" : "offerCount", GetMessage($langPrefix.'offerCount'), false, false,
    ( isset($arFields["offerCount"]) & !empty($arFields["offerCount"]) ? $arFields["offerCount"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "offers",
    array(
        '' => '...',
        'offer' => 'Offer',
    ),
    $key, $langPrefix, $arFields, true);

require (dirname(__FILE__).'/offer.php');
$tabControl->Show();
?>