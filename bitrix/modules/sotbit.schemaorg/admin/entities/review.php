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
$langPrefix = 'SOTBIT_SCHEMA_REWIEV_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: left">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Review"/>
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
$tabControl->AddEditField($key ? $key."[author]" : "author", Loc::getMessage($langPrefix.'author'), true, false,
    ( isset($arFields["author"]) & !empty($arFields["author"]) ? $arFields["author"] : "" ) );
$tabControl->AddEditField($key ? $key."[reviewBody]" : "reviewBody", Loc::getMessage($langPrefix.'reviewBody'), false, false,
    ( isset($arFields["reviewBody"]) & !empty($arFields["reviewBody"]) ? $arFields["reviewBody"] : "" ) );


$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "reviewRating",
    array(
        '' => '...',
        'rating' => 'Rating',
    ),
    $key, $langPrefix, $arFields);
require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>