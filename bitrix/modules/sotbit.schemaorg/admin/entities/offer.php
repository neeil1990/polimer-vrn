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
$langPrefix = 'SOTBIT_SCHEMA_OFFER_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Offer"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[acceptedPaymentMethod]" : "acceptedPaymentMethod", Loc::getMessage($langPrefix.'acceptedPaymentMethod'), false, false,
    ( isset($arFields["acceptedPaymentMethod"]) & !empty($arFields["acceptedPaymentMethod"]) ? $arFields["acceptedPaymentMethod"] : "" ));
$tabControl->AddEditField($key ? $key."[advanceBookingRequirement]" : "advanceBookingRequirement", Loc::getMessage($langPrefix.'advanceBookingRequirement'), false, false,
    ( isset($arFields["advanceBookingRequirement"]) & !empty($arFields["advanceBookingRequirement"]) ? $arFields["advanceBookingRequirement"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "addOn",
    array(
        '' => '...',
        'offer' => 'Offer',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[advanceBookingRequirement]" : "advanceBookingRequirement", Loc::getMessage($langPrefix.'advanceBookingRequirement'), false, false,
    ( isset($arFields["advanceBookingRequirement"]) & !empty($arFields["advanceBookingRequirement"]) ? $arFields["advanceBookingRequirement"] : "" ));
$tabControl->AddEditField($key ? $key."[availability]" : "availability", Loc::getMessage($langPrefix.'availability'), false, false,
    ( isset($arFields["availability"]) & !empty($arFields["availability"]) ? $arFields["availability"] : "" ));
$tabControl->AddEditField($key ? $key."[availabilityEnds]" : "availabilityEnds", Loc::getMessage($langPrefix.'availabilityEnds'), false, false,
    ( isset($arFields["availabilityEnds"]) & !empty($arFields["availabilityEnds"]) ? $arFields["availabilityEnds"] : "" ));
$tabControl->AddEditField($key ? $key."[availabilityStarts]" : "availabilityStarts", Loc::getMessage($langPrefix.'availabilityStarts'), false, false,
    ( isset($arFields["availabilityStarts"]) & !empty($arFields["availabilityStarts"]) ? $arFields["availabilityStarts"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "availableAtOrFrom",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[availableDeliveryMethod]" : "availableDeliveryMethod", Loc::getMessage($langPrefix.'availableDeliveryMethod'), false, false,
    ( isset($arFields["availableDeliveryMethod"]) & !empty($arFields["availableDeliveryMethod"]) ? $arFields["availableDeliveryMethod"] : "" ));
$tabControl->AddEditField($key ? $key."[businessFunction]" : "businessFunction", Loc::getMessage($langPrefix.'businessFunction'), false, false,
    ( isset($arFields["businessFunction"]) & !empty($arFields["businessFunction"]) ? $arFields["businessFunction"] : "" ));
$tabControl->AddEditField($key ? $key."[eligibleCustomerType]" : "eligibleCustomerType", Loc::getMessage($langPrefix.'eligibleCustomerType'), false, false,
    ( isset($arFields["eligibleCustomerType"]) & !empty($arFields["eligibleCustomerType"]) ? $arFields["eligibleCustomerType"] : "" ));
$tabControl->AddEditField($key ? $key."[eligibleDuration]" : "eligibleDuration", Loc::getMessage($langPrefix.'eligibleDuration'), false, false,
    ( isset($arFields["eligibleDuration"]) & !empty($arFields["eligibleDuration"]) ? $arFields["eligibleDuration"] : "" ));
$tabControl->AddEditField($key ? $key."[eligibleQuantity]" : "eligibleQuantity", Loc::getMessage($langPrefix.'eligibleQuantity'), false, false,
    ( isset($arFields["eligibleQuantity"]) & !empty($arFields["eligibleQuantity"]) ? $arFields["eligibleQuantity"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "eligibleTransactionVolume",
    array(
        '' => '...',
        'pricespecification' => 'PriceSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[includesObject]" : "includesObject", Loc::getMessage($langPrefix.'includesObject'), false, false,
    ( isset($arFields["includesObject"]) & !empty($arFields["includesObject"]) ? $arFields["includesObject"] : "" ));
$tabControl->AddEditField($key ? $key."[inventoryLevel]" : "inventoryLevel", Loc::getMessage($langPrefix.'inventoryLevel'), false, false,
    ( isset($arFields["inventoryLevel"]) & !empty($arFields["inventoryLevel"]) ? $arFields["inventoryLevel"] : "" ));
$tabControl->AddEditField($key ? $key."[itemCondition]" : "itemCondition", Loc::getMessage($langPrefix.'itemCondition'), false, false,
    ( isset($arFields["itemCondition"]) & !empty($arFields["itemCondition"]) ? $arFields["itemCondition"] : "" ));
$tabControl->AddEditField($key ? $key."[mpn]" : "mpn", Loc::getMessage($langPrefix.'mpn'), false, false,
    ( isset($arFields["mpn"]) & !empty($arFields["mpn"]) ? $arFields["mpn"] : "" ));
$tabControl->AddEditField($key ? $key."[priceValidUntil]" : "priceValidUntil", Loc::getMessage($langPrefix.'priceValidUntil'), false, false,
    ( isset($arFields["priceValidUntil"]) & !empty($arFields["priceValidUntil"]) ? $arFields["priceValidUntil"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[serialNumber]" : "serialNumber", Loc::getMessage($langPrefix.'serialNumber'), false, false,
    ( isset($arFields["serialNumber"]) & !empty($arFields["serialNumber"]) ? $arFields["serialNumber"] : "" ));
$tabControl->AddEditField($key ? $key."[sku]" : "sku", Loc::getMessage($langPrefix.'sku'), false, false,
    ( isset($arFields["sku"]) & !empty($arFields["sku"]) ? $arFields["sku"] : "" ));
$tabControl->AddEditField($key ? $key."[warranty]" : "warranty", Loc::getMessage($langPrefix.'warranty'), false, false,
    ( isset($arFields["warranty"]) & !empty($arFields["warranty"]) ? $arFields["warranty"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "category",
    array(
        '' => '...',
        'text' => 'Text',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[gtin12]" : "gtin12", Loc::getMessage($langPrefix.'gtin12'), false, false,
    ( isset($arFields["gtin12"]) & !empty($arFields["gtin12"]) ? $arFields["gtin12"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin13]" : "gtin13", Loc::getMessage($langPrefix.'gtin13'), false, false,
    ( isset($arFields["gtin13"]) & !empty($arFields["gtin13"]) ? $arFields["gtin13"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin14]" : "gtin14", Loc::getMessage($langPrefix.'gtin14'), false, false,
    ( isset($arFields["gtin14"]) & !empty($arFields["gtin14"]) ? $arFields["gtin14"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin8]" : "gtin8", Loc::getMessage($langPrefix.'gtin8'), false, false,
    ( isset($arFields["gtin8"]) & !empty($arFields["gtin8"]) ? $arFields["gtin8"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "itemOffered",
    array(
        '' => '...',
        'product' => 'Product',
        'service' => 'Service',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[price]" : "price", Loc::getMessage($langPrefix.'price'), true, false,
    ( isset($arFields["price"]) & !empty($arFields["price"]) ? $arFields["price"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "priceSpecification",
    array(
        '' => '...',
        'pricespecification' => 'PriceSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "seller",
    array(
        '' => '...',
        'organization' => 'Organization',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[priceCurrency]" : "priceCurrency", Loc::getMessage($langPrefix.'priceCurrency'), false, false,
    ( isset($arFields["priceCurrency"]) & !empty($arFields["priceCurrency"]) ? $arFields["priceCurrency"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'aggregaterating' => 'AggregateRating',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "areaServed",
    array(
        '' => '...',
//        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "eligibleRegion",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "ineligibleRegion",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[deliveryLeadTime]" : "deliveryLeadTime", Loc::getMessage($langPrefix.'deliveryLeadTime'), false, false,
    ( isset($arFields["deliveryLeadTime"]) & !empty($arFields["deliveryLeadTime"]) ? $arFields["deliveryLeadTime"] : "" ));
$tabControl->AddEditField($key ? $key."[validFrom]" : "validFrom", Loc::getMessage($langPrefix.'validFrom'), false, false,
    ( isset($arFields["validFrom"]) & !empty($arFields["validFrom"]) ? $arFields["validFrom"] : "" ));
$tabControl->AddEditField($key ? $key."[validThrough]" : "validThrough", Loc::getMessage($langPrefix.'validThrough'), false, false,
    ( isset($arFields["validThrough"]) & !empty($arFields["validThrough"]) ? $arFields["validThrough"] : "" ));

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>