<?php

function price($id){
    $ar_res_price = CPrice::GetBasePrice($id, false, false);
    if($ar_res_price['PRICE']){
        return $ar_res_price['PRICE'];
    }else{
        return false;
    }

}
function priceDiscount($id){
    global $USER;
    $ar_res_price = CCatalogProduct::GetOptimalPrice($id, 1, $USER->GetUserGroupArray(), 'N');
    if($ar_res_price['DISCOUNT_PRICE']){
        return $ar_res_price['DISCOUNT_PRICE'];
    }else{
        return false;
    }
}

function checkProduct($id){
    $ar_res = CCatalogProduct::GetByID($id);
    if($ar_res['QUANTITY'] > 0 && (float)price($id))
        return true;

    return false;
}

function getUrlProd($url){
    if($url){
        $code = explode('/',$url);
        if($code[3]){
            $code = $code[3];
        }else{
           $code = $code[2];
        }

        if(CModule::IncludeModule("iblock")) {
            $arSelect = Array("ID", "IBLOCK_ID","DETAIL_PAGE_URL","PREVIEW_PICTURE","DETAIL_PICTURE", "NAME", "PROPERTY_*");//IBLOCK_ID � ID ����������� ������ ���� �������, ��. �������� arSelectFields ����
            $arFilter = Array("IBLOCK_ID" => 21, "CODE" => $code);
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $arResults = array_merge($arFields,$arProps);
                return $arResults;
            }
        }
    }

}

AddEventHandler("main", "OnEpilog", "Redirect404");
function Redirect404() {
    if(
        !defined('ADMIN_SECTION') &&
        defined("ERROR_404") &&
        defined("PATH_TO_404") &&
        file_exists($_SERVER["DOCUMENT_ROOT"].PATH_TO_404)
    ) {

        //LocalRedirect("/404.php", "404 Not Found");
        global $APPLICATION;
        global $USER;
        $APPLICATION->RestartBuffer();

        CHTTP::SetStatus("404 Not Found");
        include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/header.php");
        include($_SERVER["DOCUMENT_ROOT"].PATH_TO_404);
        include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/footer.php");
    }
}


\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleStatusOrderChange',
    'StatusOrderChange'
);

function StatusOrderChange(\Bitrix\Main\Event $event)
{
    $status = $event->getParameter("ENTITY");

    if($status->getField('STATUS_ID') == "F"){
        $user_id = $status->getField('USER_ID');
        $rsUser = CUser::GetByID($user_id);
        if($arUser = $rsUser->Fetch()){
        $user_name = $arUser['NAME'].' '.$arUser['LAST_NAME'];
        $user_email = $arUser['EMAIL'];
        }
        $order_id = $status->getField('ID');

        if ($arOrder = CSaleOrder::GetByID($order_id))
        {
            $date_status = $arOrder['DATE_STATUS'];
        }

        $arFields = array(
            "USER_NAME" => $user_name,
            "USER_EMAIL" => $user_email,
            "ORDER_ID" => $order_id,
            "ORDER_DATE" => $date_status
        );
        CEvent::Send("ORDER_COMPLETED", "s1", $arFields);
    }
}

function AddOrderProperty($code, $value, $order)    {
    if (!strlen($code)) {
        return false;
    }
    if (CModule::IncludeModule('sale')) {
        if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' => $code))->Fetch()) {

            $db_vals = CSaleOrderPropsValue::GetList(
                array(),
                array(
                    "ORDER_ID" => $order,
                    "ORDER_PROPS_ID" => $arProp["ID"]
                )
            );
            if ($arVals = $db_vals->Fetch()) {
                CSaleOrderPropsValue::Update($arVals["ID"], array("VALUE"=>$value));
            } else {
                CSaleOrderPropsValue::Add(array(
                    'NAME' => $arProp['NAME'],
                    'CODE' => $arProp['CODE'],
                    'ORDER_PROPS_ID' => $arProp['ID'],
                    'ORDER_ID' => $order,
                    'VALUE' => $value,
                ));
            }
            //  тут можно увидеть ошибку, если что
//                global $APPLICATION;
//                var_dump($APPLICATION->GetException());
        }
    }
}

function resizeImage($id, $w, $h){
    if(!is_numeric($id) || empty($id))
        return '/bitrix/templates/main/img/no_photo.png';

    return CFile::ResizeImageGet(
        $id,
        ["width" => $w, "height" => $h],
        BX_RESIZE_IMAGE_PROPORTIONAL,
        true,
        false,
        false,
        85
    )['src'];
}

function tel($phone){
    return str_replace(['-', ' '], '', filter_var($phone, FILTER_SANITIZE_NUMBER_INT));
}

//Обнавление ИБ Каталог брендов
/*
CAgent::AddAgent(
    "CBrands::updateBrandsAgent();",
    "",
    "N",
    604800,
    "",
    "Y",
    ""
);
*/
Class CBrands
{
    public static function updateBrandsAgent()
    {
        if(CModule::IncludeModule("iblock")) {

            $arResult = [];
            $arSelect = Array(
                "ID",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "NAME",
                "PROPERTY_PROIZVODITEL"
            );

            $arFilter = Array("IBLOCK_ID" => 21, "ACTIVE" => "Y");
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $i = 0;
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                if (isset($arFields['PROPERTY_PROIZVODITEL_VALUE']) && strlen($arFields['PROPERTY_PROIZVODITEL_VALUE']) > 0 && $arFields['IBLOCK_SECTION_ID']) {
                    $brand_name = trim($arFields['PROPERTY_PROIZVODITEL_VALUE']);
                    $arResult[$brand_name]["n" . $i] = ["VALUE" => $arFields['ID']];
                }
                $i++;
            }
            if (count($arResult) > 0) {

                foreach ($arResult as $brand => $arItem) {

                    $find = CIBlockElement::GetList(Array(), ["IBLOCK_ID" => 28, "=NAME" => trim($brand)], false, false, ["ID"])->Fetch();

                    $el = new CIBlockElement;
                    $params = Array(
                        "max_len" => "100", // обрезает символьный код до 100 символов
                        "change_case" => "L", // буквы преобразуются к нижнему регистру
                        "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                        "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                        "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                        "use_google" => "false", // отключаем использование google
                    );

                    $PROP["PRODUCT"] = $arItem;
                    $arLoadProductArray = Array(
                        "IBLOCK_SECTION_ID" => false,
                        "IBLOCK_ID" => 28,
                        "PROPERTY_VALUES" => $PROP,
                        "NAME" => trim($brand),
                        "CODE" => CUtil::translit($brand, "ru" , $params),
                        "ACTIVE" => "Y"
                    );
                    if(!$el->Add($arLoadProductArray)){
                        $el->Update($find['ID'], $arLoadProductArray);
                    }
                }
            }
        }
        return "CBrands::updateBrandsAgent();";
    }
}

AddEventHandler('catalog', 'OnSuccessCatalogImport1C', 'functionOnSuccessCatalogImport1C');
function functionOnSuccessCatalogImport1C()
{
    $page = \Bitrix\Main\Composite\Page::getInstance();
    $page->deleteAll();
}

if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/checkSize.php')){
   require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/checkSize.php');
}


AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");
function bxModifySaleMails($orderID, &$eventName, &$arFields)
{
  $arOrder = CSaleOrder::GetByID($orderID);
  
  $order_props = CSaleOrderPropsValue::GetOrderProps($orderID);
  $phone="";
  $index = ""; 
  $country_name = "";
  $city_name = "";  
  $address = "";
  while ($arProps = $order_props->Fetch())
  {
    if ($arProps["CODE"] == "PHONE")
    {
       $phone = htmlspecialchars($arProps["VALUE"]);
    }
    if ($arProps["CODE"] == "LOCATION")
    {
        $arLocs = CSaleLocation::GetByID($arProps["VALUE"]);
        $country_name =  $arLocs["COUNTRY_NAME_ORIG"];
        $city_name = $arLocs["CITY_NAME_ORIG"];
    }

    if ($arProps["CODE"] == "INDEX")
    {
      $index = $arProps["VALUE"];   
    }

    if ($arProps["CODE"] == "ADDRESS")
    {
      $address = $arProps["VALUE"];
    }
  }

  $full_address = $country_name."-".$city_name.", ".$address;

  $arDeliv = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
  $delivery_name = "";
  if ($arDeliv)
  {
    $delivery_name = $arDeliv["NAME"];
  }

  $arPaySystem = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"]);
  $pay_system_name = "";
  if ($arPaySystem)
  {
    $pay_system_name = $arPaySystem["NAME"];
  }

  $arFields["ORDER_DESCRIPTION"] = $arOrder["USER_DESCRIPTION"]; 
  $arFields["PHONE"] =  $phone;
  $arFields["DELIVERY_NAME"] =  $delivery_name;
  $arFields["PAY_SYSTEM_NAME"] =  $pay_system_name;
  $arFields["FULL_ADDRESS"] = $full_address;   
}

// Отправка пароля пользователю при регистрации
AddEventHandler("main", "OnBeforeUserAdd", ["SendPassword", "onBeforeUserAddUpdate"]);
AddEventHandler("main", "OnBeforeEventAdd", ["SendPassword", "onBeforeEventAdd"]);

class SendPassword 
{
    private static $alreadySent = false;
    private static $needSend = false;
    private static $password = "";

    function onBeforeUserAddUpdate(&$arFields)
    {
        if($arFields["PASSWORD"])
        {
            self::$needSend = true;
            self::$password = $arFields["PASSWORD"];
        }
    }
	
    function onBeforeEventAdd(&$event, &$lid, &$arFields, &$message_id, &$files)
    {
        if($event!="USER_PASS_CHANGED" && $event!="USER_INFO")
            return;

        if(self::$alreadySent)
            return false;

        $arFields["PASSWORD"] = self::$password;

        self::$alreadySent = true;
    }
}
