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
$langPrefix = 'SOTBIT_SCHEMA_PRICESPECIFICATION_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", getMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="PriceSpecification"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[eligibleQuantity]" : "eligibleQuantity", Loc::getMessage($langPrefix.'eligibleQuantity'), false, false,
    ( isset($arFields["eligibleQuantity"]) & !empty($arFields["eligibleQuantity"]) ? $arFields["eligibleQuantity"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "eligibleTransactionVolume",
    array(
        '' => '...',
        'text' => 'Text',
        'pricespecification' => 'PriceSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[maxPrice]" : "maxPrice", Loc::getMessage($langPrefix.'maxPrice'), false, false,
    ( isset($arFields["maxPrice"]) & !empty($arFields["maxPrice"]) ? $arFields["maxPrice"] : "" ));
$tabControl->AddEditField($key ? $key."[minPrice]" : "minPrice", Loc::getMessage($langPrefix.'minPrice'), false, false,
    ( isset($arFields["minPrice"]) & !empty($arFields["minPrice"]) ? $arFields["minPrice"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "valueAddedTaxIncluded",
    array(
        'N' => 'N',
        'Y' => 'Y',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[price]" : "price", Loc::getMessage($langPrefix.'price'), false, false,
    ( isset($arFields["price"]) & !empty($arFields["price"]) ? $arFields["price"] : "" ));
$tabControl->AddEditField($key ? $key."[priceCurrency]" : "priceCurrency", Loc::getMessage($langPrefix.'priceCurrency'), false, false,
    ( isset($arFields["priceCurrency"]) & !empty($arFields["priceCurrency"]) ? $arFields["priceCurrency"] : "" ));
$tabControl->AddEditField($key ? $key."[validFrom]" : "validFrom", Loc::getMessage($langPrefix.'validFrom'), false, false,
    ( isset($arFields["validFrom"]) & !empty($arFields["validFrom"]) ? $arFields["validFrom"] : "" ));
$tabControl->AddEditField($key ? $key."[validThrough]" : "validThrough", Loc::getMessage($langPrefix.'validThrough'), false, false,
    ( isset($arFields["validThrough"]) & !empty($arFields["validThrough"]) ? $arFields["validThrough"] : "" ));

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>