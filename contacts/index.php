<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Контакты и адреса интернет-магазина Полимер - обращайтесь по телефону или почте, оставьте заявку и мы вам перезвоним");
$APPLICATION->SetPageProperty("title", "Контакты и адреса интернет-магазина Полимер");
$APPLICATION->SetTitle("Контакты");
?><h1>Контакты</h1>

<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.section.list",
	"contacts",
	Array(
		"ADD_SECTIONS_CHAIN" => "Y",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "N",
		"COMPONENT_TEMPLATE" => "contacts",
		"COUNT_ELEMENTS" => "Y",
		"IBLOCK_ID" => "8",
		"IBLOCK_TYPE" => "contact",
		"SECTION_CODE" => "",
		"SECTION_FIELDS" => array(0=>"",1=>"",),
		"SECTION_ID" => $_REQUEST["SECTION_ID"],
		"SECTION_URL" => "",
		"SECTION_USER_FIELDS" => array(0=>"",1=>"",),
		"SHOW_PARENT_NAME" => "Y",
		"TOP_DEPTH" => "2",
		"VIEW_MODE" => "LINE"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>