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
$langPrefix = 'SOTBIT_SCHEMA_LOCALBUSINESS_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="LocalBusiness"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[currenciesAccepted]" : "currenciesAccepted", GetMessage($langPrefix.'currenciesAccepted'), false, false,
    ( isset($arFields["currenciesAccepted"]) & !empty($arFields["currenciesAccepted"]) ? $arFields["currenciesAccepted"] : "" ));
$tabControl->AddEditField($key ? $key."[openingHours]" : "openingHours", GetMessage($langPrefix.'openingHours'), false, false,
    ( isset($arFields["openingHours"]) & !empty($arFields["openingHours"]) ? $arFields["openingHours"] : "" ));
$tabControl->AddEditField($key ? $key."[paymentAccepted]" : "paymentAccepted", GetMessage($langPrefix.'paymentAccepted'), false, false,
    ( isset($arFields["paymentAccepted"]) & !empty($arFields["paymentAccepted"]) ? $arFields["paymentAccepted"] : "" ));
$tabControl->AddEditField($key ? $key."[priceRange]" : "priceRange", GetMessage($langPrefix.'priceRange'), false, false,
    ( isset($arFields["priceRange"]) & !empty($arFields["priceRange"]) ? $arFields["priceRange"] : "" ));
$tabControl->AddEditField($key ? $key."[telephone]" : "telephone", GetMessage($langPrefix.'telephone'), false, false,
    ( isset($arFields["telephone"]) & !empty($arFields["telephone"]) ? $arFields["telephone"] : "" ));
$tabControl->AddEditField($key ? $key."[address]" : "address", GetMessage($langPrefix.'address'), false, false,
    ( isset($arFields["address"]) & !empty($arFields["address"]) ? $arFields["address"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "branchOf",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>