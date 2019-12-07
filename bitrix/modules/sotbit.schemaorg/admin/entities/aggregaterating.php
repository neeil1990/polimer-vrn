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
$langPrefix = 'SOTBIT_SCHEMA_AGGREGATERATING_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="AggregateRating"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "itemReviewed",
    array(
        '' => '...',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);


$tabControl->AddEditField($key ? $key."[ratingCount]" : "ratingCount", GetMessage($langPrefix.'ratingCount'), true, false,
    ( isset($arFields["ratingCount"]) & !empty($arFields["ratingCount"]) ? $arFields["ratingCount"] : "" ));
$tabControl->AddEditField($key ? $key."[reviewCount]" : "reviewCount", GetMessage($langPrefix.'reviewCount'), true, false,
    ( isset($arFields["reviewCount"]) & !empty($arFields["reviewCount"]) ? $arFields["reviewCount"] : "" ));

require (dirname(__FILE__).'/blocks/rating.php');
require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>