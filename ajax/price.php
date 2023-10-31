<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("iblock");

$name = strip_tags($_REQUEST['Name']);
$email = strip_tags($_REQUEST['email']);
$phone = strip_tags($_REQUEST['phone']);


$el = new CIBlockElement;

$PROP = array();
$PROP['NAME'] = $name;
$PROP['EMAIL'] = $email;
$PROP['PHONE'] = $phone;

$arLoadProductArray = Array(
  "MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
  "IBLOCK_ID"      => 27,
  "PROPERTY_VALUES"=> $PROP,
  "NAME"           => $name,
  "ACTIVE"         => "N"
  );

if($PRODUCT_ID = $el->Add($arLoadProductArray)){
	print true;
}





?>