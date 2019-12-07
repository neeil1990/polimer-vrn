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
$langPrefix = 'SOTBIT_SCHEMA_PRODUCT_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", getMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Product"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");

$tabControl->AddEditField($key ? $key."[award]" : "award", Loc::getMessage($langPrefix.'award'), false, false,
    ( isset($arFields["award"]) & !empty($arFields["award"]) ? $arFields["award"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "brand",
    array(
        '' => '...',
        'brand' => 'Brand',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[color]" : "color", Loc::getMessage($langPrefix.'color'), false, false,
    ( isset($arFields["color"]) & !empty($arFields["color"]) ? $arFields["color"] : "" ) );

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isAccessoryOrSparePartFor",
    array(
        '' => '...',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isConsumableFor",
    array(
        '' => '...',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[itemCondition]" : "itemCondition", Loc::getMessage($langPrefix.'itemCondition'), false, false,
    ( isset($arFields["itemCondition"]) & !empty($arFields["itemCondition"]) ? $arFields["itemCondition"] : "" ));
$tabControl->AddEditField($key ? $key."[logo]" : "logo", Loc::getMessage($langPrefix.'logo'), false, false,
    ( isset($arFields["logo"]) & !empty($arFields["logo"]) ? $arFields["logo"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "manufacturer",
    array(
        '' => '...',
        'organization' => 'Organization'
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[model]" : "model", Loc::getMessage($langPrefix.'model'), false, false,
    ( isset($arFields["model"]) & !empty($arFields["model"]) ? $arFields["model"] : "" ));
$tabControl->AddEditField($key ? $key."[mpn]" : "mpn", Loc::getMessage($langPrefix.'mpn'), false, false,
    ( isset($arFields["mpn"]) & !empty($arFields["mpn"]) ? $arFields["mpn"] : "" ));
$tabControl->AddEditField($key ? $key."[productID]" : "productID", Loc::getMessage($langPrefix.'productID'), false, false,
    ( isset($arFields["productID"]) & !empty($arFields["productID"]) ? $arFields["productID"] : "" ));
$tabControl->AddEditField($key ? $key."[releaseDate]" : "releaseDate", Loc::getMessage($langPrefix.'releaseDate'), false, array('class="datepicker"'),
    ( isset($arFields["releaseDate"]) & !empty($arFields["releaseDate"]) ? $arFields["releaseDate"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "reviews",
    array(
        '' => '...',
        'reviews' => 'Reviews',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl->AddEditField($key ? $key."[sku]" : "sku", Loc::getMessage($langPrefix.'sku'), false, false,
    ( isset($arFields["sku"]) & !empty($arFields["sku"]) ? $arFields["sku"] : "" ));
$tabControl->AddEditField($key ? $key."[category]" : "category", Loc::getMessage($langPrefix.'category'), false, false,
    ( isset($arFields["category"]) & !empty($arFields["category"]) ? $arFields["category"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin12]" : "gtin12", Loc::getMessage($langPrefix.'gtin12'), false, false,
    ( isset($arFields["gtin12"]) & !empty($arFields["gtin12"]) ? $arFields["gtin12"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin13]" : "gtin13", Loc::getMessage($langPrefix.'gtin13'), false, false,
    ( isset($arFields["gtin13"]) & !empty($arFields["gtin13"]) ? $arFields["gtin13"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin14]" : "gtin14", Loc::getMessage($langPrefix.'gtin14'), false, false,
    ( isset($arFields["gtin14"]) & !empty($arFields["gtin14"]) ? $arFields["gtin14"] : "" ));
$tabControl->AddEditField($key ? $key."[gtin8]" : "gtin8", Loc::getMessage($langPrefix.'gtin8'), false, false,
    ( isset($arFields["gtin8"]) & !empty($arFields["gtin8"]) ? $arFields["gtin8"] : "" ));
$tabControl->AddEditField($key ? $key."[weight]" : "weight", Loc::getMessage($langPrefix.'weight'), false, false,
    ( isset($arFields["weight"]) & !empty($arFields["weight"]) ? $arFields["weight"] : "" ));
$tabControl->AddEditField($key ? $key."[material]" : "material", Loc::getMessage($langPrefix.'material'), false, false,
    ( isset($arFields["material"]) & !empty($arFields["material"]) ? $arFields["material"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'aggregaterating' => 'aggregateRating',
    ),
    $key, $langPrefix, $arFields);
$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[additionalProperty]" : "additionalProperty", Loc::getMessage($langPrefix.'additionalProperty'), false, false,
    ( isset($arFields["additionalProperty"]) & !empty($arFields["additionalProperty"]) ? $arFields["additionalProperty"] : "" ));
$tabControl->AddEditField($key ? $key."[productionDate]" : "productionDate", Loc::getMessage($langPrefix.'productionDate'), false, array('class="datepicker"'),
    ( isset($arFields["productionDate"]) & !empty($arFields["productionDate"]) ? $arFields["productionDate"] : "" ));
$tabControl->AddEditField($key ? $key."[purchaseDate]" : "purchaseDate", Loc::getMessage($langPrefix.'purchaseDate'), false, array('class="datepicker"'),
    ( isset($arFields["purchaseDate"]) & !empty($arFields["purchaseDate"]) ? $arFields["purchaseDate"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "offers",
    array(
        '' => '...',
        'offers' => 'Offers',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isRelatedTo",
    array(
        '' => '...',
        'product' => 'Product',
        'service' => 'Service',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isSimilarTo",
    array(
        '' => '...',
        'product' => 'Product',
        'service' => 'Service',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[audience]" : "audience", Loc::getMessage($langPrefix.'audience'), false, false,
    ( isset($arFields["audience"]) & !empty($arFields["audience"]) ? $arFields["audience"] : "" ));
$tabControl->AddEditField($key ? $key."[awards]" : "awards", Loc::getMessage($langPrefix.'awards'), false, false,
    ( isset($arFields["awards"]) & !empty($arFields["awards"]) ? $arFields["awards"] : "" ));
require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>