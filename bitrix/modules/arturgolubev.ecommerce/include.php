<?
use \Arturgolubev\Ecommerce\Tools as Tools;
use \Arturgolubev\Ecommerce\Unitools as UTools;

Class CArturgolubevEcommerce 
{
	const MODULE_ID = 'arturgolubev.ecommerce';
	var $MODULE_ID = 'arturgolubev.ecommerce'; 
	
	const SESS = 'AG_ECOMMERCE';
	
	// f
	function convertCurrencyBasket($proudctsArray){
		if(is_array($proudctsArray))
		{
			$convCurrency = UTools::getSiteSetting("convert_currency");
			if($convCurrency && CModule::IncludeModule('currency'))
			{
				foreach($proudctsArray as $k=>$basket){
					if($basket["CURRENCY"] == $convCurrency) continue;
					
					$proudctsArray[$k]["PRICE"] = round(CCurrencyRates::ConvertCurrency($basket["PRICE"], $basket["CURRENCY"], $convCurrency), 2);
					$proudctsArray[$k]["CURRENCY"] = $convCurrency;
				}
			}
		}
		
		return $proudctsArray;
	}
	
	// p
	function ProtectEpilogStart(){
		if (UTools::checkStatus() && !Tools::checkDisable()){
			$stopAjaxOnOrder = (Tools::isOrderPage() && UTools::getSetting('off_ajax_order') == 'Y');
			
			if(UTools::getSetting('off_ajax') != 'Y' && !$stopAjaxOnOrder)
			{
				CJSCore::Init(array('ajax'));
				UTools::addJs("/bitrix/js/arturgolubev.ecommerce/script_v2.js");
			}
		}
	}
	function onBufferContent(&$bufferContent){
		if (UTools::checkStatus() && !Tools::checkDisable()){
			if(Tools::isOrderPage())
				self::getScriptBeginingCheckout();
			
			$finalScripts = self::checkReadyEvents(1);
			if($finalScripts)
			{
				$bufferContent = UTools::addBodyScript('<script>'.$finalScripts.'</script>', $bufferContent);
			}
		}
	}
	
	// s
	function onOrderAdd($orderId, $arFields){
		if(!Tools::checkDisable()){
			if(UTools::getSetting('debug') == 'Y'){
				AddMessage2Log("orderId = ".$orderId, "Ag Ec onOrderAdd");
			}
			
			$_SESSION["AG_ECOMMERCE"]["ORDERS_TO_SEND"][$orderId] = $orderId;
		}
	}
	function onBasketAdd($basketID, $arFields){
		if(!Tools::checkDisable()){
			if(UTools::getSetting('debug') == 'Y'){
				AddMessage2Log("basketID = ".$basketID, "Ag Ec onBasketAdd");
			}
			
			$productInfo = self::getBasketProductInfo($basketID, $arFields); 
			$_SESSION["AG_ECOMMERCE"]["ADD_TO_BASKET"][] = $productInfo;
		}
	}
	function onBasketDelete($ID){
		if(!Tools::checkDisable()){
			if(UTools::getSetting('debug') == 'Y'){
				AddMessage2Log("ID = ".$ID, "Ag Ec onBasketDelete");
			}
		
			$productInfo = self::getBasketProductInfo($ID); 
			$_SESSION["AG_ECOMMERCE"]["DELETE_FROM_BASKET"][] = $productInfo;
		}
	}
	
	// d
	function getProductInfo($productId){
		if(Tools::checkDisable()) return false;
		if(!CModule::IncludeModule("iblock") || !$productId) return false;
		
		$item = array(
			"ID" => $productId
		);
		
		$res = CIBlockElement::GetList(Array(), Array("ID"=>$productId), false, Array("nPageSize"=>1), Array("ID", "NAME", "IBLOCK_ID", "SECTION_ID", "IBLOCK_SECTION_ID"));
		while($ob = $res->GetNextElement())
		{
			$arFields = $ob->GetFields();
			$arFields["PROPERTIES"] = $ob->GetProperties();
			
			if($arFields["IBLOCK_SECTION_ID"])
				$intSectionID = $arFields["IBLOCK_SECTION_ID"];
			
			$item["NAME"] = Tools::textSafeMode($arFields["NAME"], 1);
			$item["IBLOCK_SECTION_ID"] =  $arFields["IBLOCK_SECTION_ID"];
			
			$val = UTools::getSetting('BRAND_PROPERTY_'.$arFields["IBLOCK_ID"]);
			if($val)
			{
				foreach($arFields["PROPERTIES"] as $arProp)
				{
					if($val == $arProp["ID"])
					{
						$tmp = \CIBlockFormatProperties::GetDisplayValue($arFields, $arProp, 'evt1');
						if($tmp["DISPLAY_VALUE"])
						{
							if(is_array($tmp["DISPLAY_VALUE"]))
								$item["BRAND"] = strip_tags(implode('/',$tmp["DISPLAY_VALUE"]));
							else
								$item["BRAND"] = strip_tags($tmp["DISPLAY_VALUE"]);
							
							$item["BRAND"] = Tools::textSafeMode($item["BRAND"], 1);
						}
					}
				}
			}
			
			if($intSectionID)
			{
				$nav = CIBlockSection::GetNavChain(false, $intSectionID);
				while($pathFields = $nav->Fetch()){
					$item["SECTION_NAME"] .= ($item["SECTION_NAME"] != '') ? ' / ' : '';
					$item["SECTION_NAME"] .= $pathFields["NAME"];
				}
				
				$item["SECTION_NAME"] = Tools::textSafeMode($item["SECTION_NAME"], 1);
			}
		}
		
		return $item;
	}
	function getBasketProductInfo($basketId, $arFields = array()){
		if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("catalog") || !CModule::IncludeModule("sale")) return false;
		if(!$basketId) return false;
		
		if(empty($arFields))
		{
			$arFields = CSaleBasket::GetByID($basketId);
		}
		
		if(true)
		{
			$mxResult = CCatalogSku::GetProductInfo($arFields["PRODUCT_ID"]);
			if($mxResult)
			{
				$productInfo = self::getProductInfo($mxResult['ID']);
				
				$skuInfo = self::getProductInfo($arFields["PRODUCT_ID"]);
				$productInfo["ID"] = $skuInfo["ID"];
				$productInfo["NAME"] = $skuInfo["NAME"];
			}
			else
			{
				$productInfo = self::getProductInfo($arFields["PRODUCT_ID"]);
			}
		}
		
		if(true)
		{
			if(!$arFields["ORDER_ID"])
			{
				$arOptimalPrice = CCatalogProduct::GetOptimalPrice($arFields["PRODUCT_ID"]);
				$arFields["CURRENCY"] = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
				$arFields["PRICE"] = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
			}
			
			$productInfo["PRICE"] = $arFields["PRICE"];
			$productInfo["CURRENCY"] = $arFields["CURRENCY"];
			$productInfo["QUANTITY"] = ($arFields["QUANTITY"]) ? $arFields["QUANTITY"] : '1';
		}
		
		if(empty($arFields["PROPS"]))
		{
			$dbProps = CSaleBasket::GetPropsList(array(), array("BASKET_ID" => $basketId));
			while ($arPropsFields = $dbProps->Fetch())
			{
				$arFields["PROPS"][] = $arPropsFields;
			}
		}
		
		if(!empty($arFields["PROPS"]))
		{
			foreach($arFields["PROPS"] as $arPropsFields)
			{
				if($arPropsFields["CODE"] != 'CATALOG.XML_ID' && $arPropsFields["CODE"] != 'PRODUCT.XML_ID')
				{
					$productInfo["PROPS_VALUE"][] = Tools::textSafeMode($arPropsFields["VALUE"], 1);
				}
			}
		}
		
		$productInfo["NAME"] = Tools::textSafeMode($productInfo["NAME"], 1);
		
		return $productInfo;
	}
	
	// c
	function checkReadyEvents($use_document_ready = 0){
		if(Tools::checkDisable()) return false;
			
		$structure = array(
			array("SESSION_PARAM" => "ADD_TO_BASKET", "COOKIE_PARAM" => "EC_ADD_FOR_"),
			array("SESSION_PARAM" => "DELETE_FROM_BASKET", "COOKIE_PARAM" => "EC_RM_FOR_"),
			array("SESSION_PARAM" => "ORDERS_TO_SEND", "COOKIE_PARAM" => "EC_SHOW_FOR_"),
		);
		
		foreach($structure as $arParams)
		{
			if(!empty($_SESSION["AG_ECOMMERCE"][$arParams["SESSION_PARAM"]]))
			{
				foreach($_SESSION["AG_ECOMMERCE"][$arParams["SESSION_PARAM"]] as $key=>$val)
				{
					$cookieName = $arParams["COOKIE_PARAM"].$key;
					if($_COOKIE[$cookieName] == 'Y')
					{
						setcookie($cookieName, "", time()-1000, "/");
						unset($_SESSION["AG_ECOMMERCE"][$arParams["SESSION_PARAM"]][$key]);
					}
				}
			}
		}
		
		$cacheScripts = UTools::getStorage("scripts", "move_footer");
		
		$actionScript = '';
		if(!$actionScript) $actionScript .= self::getScriptForNewOrder($_SESSION["AG_ECOMMERCE"]["ORDERS_TO_SEND"], $use_document_ready);
		if(!$actionScript) $actionScript .= self::getScriptForAddProducts($_SESSION["AG_ECOMMERCE"]["ADD_TO_BASKET"], 'add', $use_document_ready);
		if(!$actionScript) $actionScript .= self::getScriptForAddProducts($_SESSION["AG_ECOMMERCE"]["DELETE_FROM_BASKET"], 'remove', $use_document_ready);
		
		return $cacheScripts.$actionScript;
	}
	
	// g
	function getScriptForNewOrder($arOrders, $use_document_ready){
		if(empty($arOrders)) return false;
		if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("catalog") || !CModule::IncludeModule("sale")) return false;
		
		foreach($arOrders as $id)
		{
			$orderId = $id;
			break;
		}
		
		$cookieName = "EC_SHOW_FOR_".$orderId;
		$containerName = UTools::getSiteSettingEx('container', 'dataLayer');
		$get_order_id_from = UTools::getSetting('get_order_id_from', 'ID');
		
		$cookie = ''; $yandex = ''; $google = ''; $fb = '';
		
		$order = array(
			"ID" => $orderId,
		);
		
		$rsSales = CSaleOrder::GetList(array(), array("ID" => $order["ID"]), false, false, array("BASKET_DISCOUNT_COUPON", "*"));
		if($arSales = $rsSales->Fetch())
		{
			$order["FIELDS"] = $arSales;
		}
			
		if($order["FIELDS"])
		{
			$orderNum = Tools::textSafeMode($order["FIELDS"][$get_order_id_from], 1);
			
			$dbBasketItems = CSaleBasket::GetList(array("ID" => "ASC"), array("ORDER_ID" => $order["FIELDS"]["ID"]), false, false, array("*"));
			while ($arItems = $dbBasketItems->Fetch())
			{
				$productInfo = self::getBasketProductInfo($arItems["ID"], $arItems); 
				$order["ORDER_BASKET"][] = $productInfo;
			}
			
			$order["ORDER_BASKET"] = self::convertCurrencyBasket($order["ORDER_BASKET"]);
			foreach($order["ORDER_BASKET"] as $basket)
			{
				$currency = $basket["CURRENCY"];
				break;
			}
			
			if($order["FIELDS"]["CURRENCY"] != $currency)
			{
				$order["FIELDS"]["PRICE"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["PRICE"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				$order["FIELDS"]["TAX_VALUE"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["TAX_VALUE"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				$order["FIELDS"]["PRICE_DELIVERY"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["PRICE_DELIVERY"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				
				$order["FIELDS"]["CURRENCY"] = $currency;
			}
			
			$productsJsString = '';
			foreach($order["ORDER_BASKET"] as $arItem):
				if($productsJsString) $productsJsString .= ', ';
				$productsJsString .= '{';
					$productsJsString .= '"id": "'.$arItem["ID"].'",';
					$productsJsString .= '"name": "'.$arItem["NAME"].'",';
					$productsJsString .= '"price": '.($arItem["PRICE"]*1).',';
					$productsJsString .= '"category": "'.$arItem["SECTION_NAME"].'",';
					$productsJsString .= '"quantity": "'.$arItem["QUANTITY"].'",';
					$productsJsString .= '"brand": "'.$arItem["BRAND"].'",';
					$productsJsString .= '"variant": "'.(($arItem["PROPS_VALUE"]) ? implode('/', $arItem["PROPS_VALUE"]) : '').'",';
				$productsJsString .= '}';
			endforeach;
			
			$cookie .= ($use_document_ready) ? 'document.addEventListener("DOMContentLoaded", function(){' : '';
				$cookie .= 'var expires = new Date((new Date).getTime() + (6 * 30 * 24 * 60 * 60 * 4000)); ';
				$cookie .= 'var cookie_string = "'.$cookieName.'" + "=" + escape("Y"); ';
				$cookie .= 'cookie_string += "; expires=" + expires.toUTCString(); ';
				$cookie .= 'cookie_string += "; path=" + escape ("/"); ';
				$cookie .= 'document.cookie = cookie_string; ';
				
				if(UTools::getSetting('debug') == 'Y')
					$cookie .= 'console.log("setCookie: " + cookie_string); ';
			$cookie .= ($use_document_ready) ? '});' : '';
				
			if(UTools::getSiteSetting("ya_off") != 'Y'){
				$yandex .= 'window.'.$containerName.' = window.'.$containerName.' || [];';
				$yandex .= 'var sendToMetric = {';
					$yandex .= '"ecommerce": {';
						$yandex .= '"currencyCode": "'.$order["FIELDS"]["CURRENCY"].'",';
						$yandex .= '"purchase": {';
							$yandex .= '"actionField": {';
								$yandex .= '"id" : "'.$orderNum.'",';
								$yandex .= '"revenue" : "'.($order["FIELDS"]["PRICE"]*1).'",';
								$yandex .= '"coupon" : "'.($order["FIELDS"]["BASKET_DISCOUNT_COUPON"]).'",'; 
								
								if(IntVal(UTools::getSiteSettingEx('yandex_target_order')) > 0)
									$yandex .= '"goal_id" : "'.IntVal(UTools::getSiteSettingEx('yandex_target_order')).'",'; 
							
							$yandex .= '},';
							$yandex .= '"products": ['.$productsJsString.']';
						$yandex .= '}';
					$yandex .= '}';
				$yandex .= '};';
				$yandex .= 'window.'.$containerName.'.push(sendToMetric);';
				
				if(UTools::getSetting('debug') == 'Y'){
					$yandex .= 'console.log("EC: Check yandex purchase"); ';
					$yandex .= 'console.log(sendToMetric);';
				}
			}
			
			if(UTools::getSiteSetting("ga_off") != 'Y'){
				if(UTools::getSetting('old_analitycs_version') != 'Y'){
					$google .= 'if (typeof gtag != "function") {function gtag(){dataLayer.push(arguments);}};';
					$google .= 'try {';
						$google .= 'gtag("event", "purchase", {';
							$google .= "'id' : '".$orderNum."',";
							$google .= "'transaction_id' : '".$orderNum."',";
							$google .= "'affiliation' : '".SITE_SERVER_NAME."',";
							$google .= "'value' : '".($order["FIELDS"]["PRICE"]*1)."',";
							$google .= "'tax' : '".($order["FIELDS"]["TAX_VALUE"]*1)."',";
							$google .= "'shipping' : '".($order["FIELDS"]["PRICE_DELIVERY"]*1)."',";
							$google .= "'coupon' : '".($order["FIELDS"]["BASKET_DISCOUNT_COUPON"])."',"; 
							$google .= "'currency' : '".$order["FIELDS"]["CURRENCY"]."',"; 
							$google .= '"items": ['.$productsJsString.']';
						$google .= '});';
					$google .= '}catch(err){console.log("EC Warning: gtag() not function");}';
				}
				else
				{
					// old code
					$google .= 'try {';
						$google .= "ga('require', 'ec');";
						$google .= "ga('set', 'currencyCode', '".$order["FIELDS"]["CURRENCY"]."');";
						
						foreach($order["ORDER_BASKET"] as $arItem):
							$google .= "ga('ec:addProduct', {";
								$google .= "'id': '".$arItem["ID"]."',";
								$google .= "'name': '".$arItem["NAME"]."',";
								$google .= "'price': ".($arItem["PRICE"]*1).",";
								$google .= "'category': '".$arItem["SECTION_NAME"]."',";
								$google .= "'quantity': '".$arItem["QUANTITY"]."',";
								$google .= "'brand': '".$arItem["BRAND"]."',";
								$google .= "'variant': '".(($arItem["PROPS_VALUE"]) ? implode('/', $arItem["PROPS_VALUE"]) : '')."',";
							$google .= "});";
						endforeach;
						
						$google .= "ga('ec:setAction', 'purchase', {";
							$google .= "'id' : '".$orderNum."',";
							$google .= "'affiliation' : '".SITE_SERVER_NAME."',";
							$google .= "'revenue' : '".($order["FIELDS"]["PRICE"]*1)."',";
							$google .= "'tax' : '".($order["FIELDS"]["TAX_VALUE"]*1)."',";
							$google .= "'shipping' : '".($order["FIELDS"]["PRICE_DELIVERY"]*1)."',";
							$google .= "'coupon' : '".($order["FIELDS"]["BASKET_DISCOUNT_COUPON"])."',"; 
						$google .= "});";
						
						$google .= "ga('send', 'pageview');";
					$google .= '}catch(err){console.log("New Order send with ga() has error");}';
				}
			}
			
			if(UTools::getSiteSetting("fb_off") != 'Y'){
				$ids = array();
				$value = 0;
				$productsFb = '';
				
				foreach($order["ORDER_BASKET"] as $item){
					$ids[] = $item["ID"];
					$currency = $item["CURRENCY"];
					$value += $item["PRICE"]*$item["QUANTITY"];
					
					if($productsFb) $productsFb .= ', ';
					$productsFb .= '{';
						$productsFb .= '"id": "'.$item["ID"].'",';
						$productsFb .= '"quantity": "'.$item["QUANTITY"].'",';
					$productsFb .= '}';
				}
				
				$fb .= 'var fbparam = {';
					$fb .= '"content_ids": '.'['.'"'.implode('","', $ids).'"'.']'.',';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$currency.'",';
					$fb .= '"value": "'.$value.'",';
					$fb .= '"contents": ['.$productsFb.'],';
					$fb .= '"num_items": "'.count($ids).'",';
				$fb .= '};';
				
				if(UTools::getSetting('debug') == 'Y')
				{
					$fb .= 'console.log("EC: Check facebook Purchase");';
					$fb .= 'console.log(fbparam);';
				}
				
				$fb .= 'if (typeof fbq == "function"){ fbq("track", "Purchase", fbparam); } else { console.log("EC Warning: fbq() not function"); };';
			}
			
			$fullScript = $cookie.$yandex.$google.$fb;
			$fullScript = str_replace(array("\r\n", "\r", "\n"), '',  $fullScript);
			
			return $fullScript;
		}
	}
	
	function getDetailCode($productId, $offersProps = array()){
		if(Tools::checkDisable() || !$productId) return false;
		if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("catalog")) return false;
		
		$yandex = ''; $google = ''; $fb = '';
		
		$arData = array();
		$containerName = UTools::getSiteSettingEx('container', 'dataLayer');
		
		$arData["PRODUCT"] = self::getProductInfo($productId);
		
		$res = CCatalogSKU::getOffersList($productId);
		$arOfferIDs = $res[$productId];
		if(!empty($arOfferIDs))
		{
			$arSelect = Array("ID", "NAME");
			
			foreach($offersProps as $prop)
				$arSelect[] = "PROPERTY_".$prop;;
			
			$arFilter = Array("ID"=>array_keys($arOfferIDs), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
			$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
			while($ob = $res->GetNextElement())
			{
				$arFields = $ob->GetFields();
				
				$arFields["PROPS_OFFER"] = array();
				foreach($offersProps as $prop)
					if($arFields["PROPERTY_".$prop."_VALUE"])
						$arFields["PROPS_OFFER"][] = Tools::textSafeMode($arFields["PROPERTY_".$prop."_VALUE"], 1);
				
				$arOptimalPrice = CCatalogProduct::GetOptimalPrice($arFields["ID"]);
				if(!empty($arOptimalPrice))
				{
					$tp = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
					$tc = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
					
					$arData["ITEMS"][$arFields["ID"]] = array(
						"ID" => $arFields["ID"],
						"NAME" => Tools::textSafeMode($arFields["NAME"]),
						"CATEGORY" => $arData["PRODUCT"]["SECTION_NAME"],
						"BRAND" => $arData["PRODUCT"]["BRAND"],
						"CURRENCY" => $tc,
						"PRICE" => $tp,
						"PROPS_OFFER" => $arFields["PROPS_OFFER"],
					);
				}
			}
		}
		else
		{
			$arOptimalPrice = CCatalogProduct::GetOptimalPrice($productId);
			if(!empty($arOptimalPrice))
			{
				$tp = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
				$tc = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
				
				$arData["ITEMS"][$productId] = array(
					"ID" => $productId,
					"NAME" => $arData["PRODUCT"]["NAME"],
					"CATEGORY" => $arData["PRODUCT"]["SECTION_NAME"],
					"BRAND" => $arData["PRODUCT"]["BRAND"],
					"CURRENCY" => $tc,
					"PRICE" => $tp,
				);
			}
		}
		
		if(!empty($arData["ITEMS"]))
		{
			$arData["ITEMS"] = self::convertCurrencyBasket($arData["ITEMS"]);
			foreach($arData["ITEMS"] as $basket)
			{
				$currency = $basket["CURRENCY"];
				break;
			}
			
			
			$productsJsString = '';
			foreach($arData["ITEMS"] as $arItem){
				if($productsJsString) $productsJsString .= ', ';
				$productsJsString .= '{';
					$productsJsString .= '"id": "'.$arItem["ID"].'",';
					$productsJsString .= '"name": "'.$arItem["NAME"].'",';
					$productsJsString .= '"price": '.($arItem["PRICE"]*1).',';
					$productsJsString .= '"category": "'.$arItem["CATEGORY"].'",';
					$productsJsString .= '"brand": "'.$arItem["BRAND"].'",';
					$productsJsString .= ((!empty($arItem["PROPS_OFFER"]))) ? '"variant": "'.implode('/', $arItem["PROPS_OFFER"]).'",' : '';
				$productsJsString .= '}';
			}
			
			if(UTools::getSiteSetting("ya_off") != 'Y'){
				$yandex .= 'window.'.$containerName.' = window.'.$containerName.' || [];';
				$yandex .= 'var agSendDetailInfo = {';
					$yandex .= '"ecommerce": {';
						$yandex .= ($currency) ? '"currencyCode": "'.$currency.'",' : '';
						$yandex .= '"detail": {"products": ['.$productsJsString.']}';
					$yandex .= '}';
				$yandex .= '};';
				$yandex .= 'window.'.$containerName.'.push(agSendDetailInfo);';
				
				if(UTools::getSetting('debug') == 'Y')
				{
					$yandex .= 'console.log("EC: Check yandex detail"); ';
					$yandex .= 'console.log(agSendDetailInfo.ecommerce);';
				}
			}
			
			if(UTools::getSiteSetting("ga_off") != 'Y'){
				if(UTools::getSetting('old_analitycs_version') != 'Y')
				{
					$google = 'if (typeof gtag != "function") {function gtag(){dataLayer.push(arguments);}};';
					$google .= 'try {';
						$google .= "gtag('event', 'view_item', {'items': [".$productsJsString."]});";
					$google .= '}catch(err){console.log("EC Warning: gtag() not function");}';
				}
				else
				{
					// old code
					$google = 'try {';
						$google .= "ga('require', 'ec');";
						$google .= "ga('set', 'currencyCode', '".$currency."');";
						foreach($arData["ITEMS"] as $arItem):
							$google .= "ga('ec:addProduct', {";
								$google .= '"id": "'.$arItem["ID"].'",';
								$google .= '"name": "'.$arItem["NAME"].'",';
								$google .= '"price": '.($arItem["PRICE"]*1).',';
								$google .= '"category": "'.$arItem["CATEGORY"].'",';
								$google .= '"brand": "'.$arItem["BRAND"].'",';
								if(!empty($arItem["PROPS_OFFER"]))
								$google .= '"variant": "'.implode('/', $arItem["PROPS_OFFER"]).'",';
							$google .= "});";
						endforeach;
						$google .= "ga('ec:setAction', 'detail');";
						$google .= "ga('send', 'pageview');";
					$google .= '}catch(err){console.log("Detail send with ga() has error");}';
				}
			}
			
			
			if(UTools::getSiteSetting("fb_off") != 'Y'){
				$cItem = current($arData["ITEMS"]);
				
				$fb .= 'var fbparam = {';
					$fb .= '"content_ids": '.'['.'"'.implode('","', array_keys($arData["ITEMS"])).'"'.']'.',';
					$fb .= '"content_category": "'.$cItem["CATEGORY"].'",';
					$fb .= '"content_name": "'.$cItem["NAME"].'",';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$cItem["CURRENCY"].'",';
					$fb .= '"value": "'.$cItem["PRICE"].'",';
				$fb .= '};';
				
				if(UTools::getSetting('debug') == 'Y')
				{
					$fb .= 'console.log("EC: Check facebook ViewContent");';
					$fb .= 'console.log(fbparam);';
				}
				
				$fb .= 'if (typeof fbq == "function"){ fbq("track", "ViewContent", fbparam); } else { console.log("EC Warning: fbq() not function"); };';
			}
			
			$result = $yandex.$google.$fb;
		}
		
		return $result;
	}
	
	function getScriptForAddProducts($proudctsArray, $type, $use_document_ready){		
		if(empty($proudctsArray)) return false;
		
		$cookie = ''; $yandex = ''; $google = ''; $fb = '';
		$containerName = UTools::getSiteSettingEx('container', 'dataLayer');
		
		$cookie .= ($use_document_ready) ? 'document.addEventListener("DOMContentLoaded", function(){' : '';
			foreach($proudctsArray as $key=>$val)
			{
				$cookieName = ($type == 'add') ? "EC_ADD_FOR_".$key : "EC_RM_FOR_".$key;
				
				$cookie .= 'var expires = new Date((new Date).getTime() + (6 * 30 * 24 * 60 * 60 * 1000)); ';
				$cookie .= 'var cookie_string = "'.$cookieName.'" + "=" + escape("Y"); ';
				$cookie .= 'cookie_string += "; expires=" + expires.toUTCString(); ';
				$cookie .= 'cookie_string += "; path=" + escape ("/"); ';
				$cookie .= 'document.cookie = cookie_string; ';
				
				if(UTools::getSetting('debug') == 'Y')
					$cookie .= 'console.log("setCookie: " + cookie_string); ';
			}
		$cookie .= ($use_document_ready) ? '});' : '';
		
		$proudctsArray = self::convertCurrencyBasket($proudctsArray);
		foreach($proudctsArray as $basket)
		{
			$currency = $basket["CURRENCY"];
			break;
		}
		
		$productsJsString = '';
		foreach($proudctsArray as $arItem):
			if($productsJsString) $productsJsString .= ', ';
			$productsJsString .= '{';
				$productsJsString .= '"id": "'.$arItem["ID"].'",';
				$productsJsString .= '"name": "'.$arItem["NAME"].'",';
				$productsJsString .= '"price": '.($arItem["PRICE"]*1).',';
				$productsJsString .= '"category": "'.$arItem["SECTION_NAME"].'",';
				$productsJsString .= '"quantity": "'.($arItem["QUANTITY"]*1).'",';
				$productsJsString .= '"brand": "'.$arItem["BRAND"].'",';
				$productsJsString .= ((!empty($arItem["PROPS_VALUE"]))) ? '"variant": "'.implode('/', $arItem["PROPS_VALUE"]).'",' : '';
			$productsJsString .= '}';
		endforeach;
		
		
		if(UTools::getSiteSetting("ya_off") != 'Y'){
			$yandex .= 'window.'.$containerName.' = window.'.$containerName.' || []; ';
			$yandex .= 'var sendToMetric = {';
				$yandex .= ($currency) ? '"currencyCode": "'.$currency.'",' : '';
				$yandex .= '"ecommerce": {';
					$yandex .= '"'.$type.'": {"products": ['.$productsJsString.']}';
				$yandex .= '}';
			$yandex .= '}; ';
			$yandex .= 'window.'.$containerName.'.push(sendToMetric);';
			
			if(UTools::getSetting('debug') == 'Y')
			{
				$yandex .= 'console.log("EC: Check yandex '.$type.'"); ';
				$yandex .= 'console.log(sendToMetric);';
			}
		}
		
		if(UTools::getSiteSetting("ga_off") != 'Y'){
			if(UTools::getSetting('old_analitycs_version') != 'Y'){
				$google = 'if (typeof gtag != "function") {function gtag(){dataLayer.push(arguments);}};';
				$google .= 'try {';
					if($type == 'add')
						$google .= "gtag('event', 'add_to_cart', {'items': [".$productsJsString."]});";
					else
						$google .= "gtag('event', 'remove_from_cart', {'items': [".$productsJsString."]});";
				$google .= '}catch(err){console.log("EC Warning: gtag() not function");}';
			}
			else
			{
				//old code
				$google .= 'try {';
					$google .= "ga('require', 'ec');";
					$google .= "ga('set', 'currencyCode', '".$currency."');";
					foreach($proudctsArray as $arItem):
						$google .= "ga('ec:addProduct', {";
							$google .= "'id': '".$arItem["ID"]."',";
							$google .= "'name': '".$arItem["NAME"]."',";
							$google .= "'price': ".($arItem["PRICE"]*1).",";
							$google .= "'category': '".$arItem["SECTION_NAME"]."',";
							$google .= "'quantity': '".$arItem["QUANTITY"]."',";
							$google .= "'brand': '".$arItem["BRAND"]."',";
							$google .= "'variant': '".(($arItem["PROPS_VALUE"]) ? implode('/', $arItem["PROPS_VALUE"]) : '')."',";
						$google .= "});";
					endforeach;
					
					$google .= "ga('ec:setAction', '".$type."');";
				$google .= '}catch(err){console.log("Basket Update send with ga() has error");}';
			}
		}
		
		if(UTools::getSiteSetting("fb_off") != 'Y' && $type == 'add'){
			foreach($proudctsArray as $arItem){
				$fb .= 'var fbparam = {';
					$fb .= '"content_name": "'.$arItem["NAME"].'",';
					$fb .= '"content_ids": ["'.$arItem["ID"].'"],';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$arItem["CURRENCY"].'",';
					$fb .= '"value": "'.$arItem["PRICE"].'",'; 
					$fb .= '"contents": [{"id": "'.$arItem["ID"].'", "quantity": "'.$arItem["QUANTITY"].'"}],';
				$fb .= '};';
				
				if(UTools::getSetting('debug') == 'Y'){
					$fb .= 'console.log("EC: Check facebook AddToCart");';
					$fb .= 'console.log(fbparam);';
				}
				
				$fb .= 'if (typeof fbq == "function"){ fbq("track", "AddToCart", fbparam); } else { console.log("EC Warning: fbq() not function"); };';
			}
		}
		
		$fullScript = $cookie.$yandex.$google.$fb;
		$fullScript = str_replace(array("\r\n", "\r", "\n"), '',  $fullScript);
		
		return $fullScript;
	}
	
	function getScriptBeginingCheckout(){
		if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("catalog") || !CModule::IncludeModule("sale")) return false;
		
		$dbBasketItems = CSaleBasket::GetList(array("ID" => "ASC"), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL"), false, false, array("*"));
		while ($arItems = $dbBasketItems->Fetch())
		{
			$productInfo = self::getBasketProductInfo($arItems["ID"], $arItems); 
			$arData["ORDER_BASKET"][] = $productInfo;
		}
		
		$arData["ORDER_BASKET"] = self::convertCurrencyBasket($arData["ORDER_BASKET"]);
		
		if(!empty($arData["ORDER_BASKET"]))
		{
			$google = ''; $fb = '';
			
			if(UTools::getSetting('old_analitycs_version') != 'Y' && UTools::getSiteSetting("ga_off") != 'Y'){
				
				$productsJsString = '';
				foreach($arData["ORDER_BASKET"] as $arItem){
					if($productsJsString) $productsJsString .= ', ';
					$productsJsString .= '{';
						$productsJsString .= '"id": "'.$arItem["ID"].'",';
						$productsJsString .= '"name": "'.$arItem["NAME"].'",';
						$productsJsString .= '"price": '.($arItem["PRICE"]*1).',';
						$productsJsString .= '"category": "'.$arItem["SECTION_NAME"].'",';
						$productsJsString .= '"quantity": "'.$arItem["QUANTITY"].'",';
						$productsJsString .= '"brand": "'.$arItem["BRAND"].'",';
						$productsJsString .= '"variant": "'.(($arItem["PROPS_VALUE"]) ? implode('/', $arItem["PROPS_VALUE"]) : '').'",';
					$productsJsString .= '}';
				}
				
				$google .= 'var gaparam = {';
					$google .= '"items": ['.$productsJsString.'],';
				$google .= '};';
				
				
				$google .= 'if (typeof gtag != "function") {function gtag(){dataLayer.push(arguments);}};';
				$google .= 'try { gtag("event", "begin_checkout", gaparam); } catch(err) { console.log("EC Warning: gtag() not function"); };';
				
				if(UTools::getSetting('debug') == 'Y'){
					$google .= 'console.log("EC: Check analitycs begin_checkout");';
					$google .= 'console.log(gaparam);';
				}
			}
			
			if(UTools::getSiteSetting("fb_off") != 'Y'){
				$ids = array();
				$value = 0;
				$productsFb = '';
				
				foreach($arData["ORDER_BASKET"] as $item){
					$ids[] = $item["ID"];
					$currency = $item["CURRENCY"];
					$value += $item["PRICE"]*$item["QUANTITY"];
					
					if($productsFb) $productsFb .= ', ';
					$productsFb .= '{';
						$productsFb .= '"id": "'.$item["ID"].'",';
						$productsFb .= '"quantity": "'.$item["QUANTITY"].'",';
					$productsFb .= '}';
				}
				
				$fb .= 'var fbparam = {';
					$fb .= '"content_ids": '.'['.'"'.implode('","', $ids).'"'.']'.',';
					$fb .= '"currency": "'.$currency.'",';
					$fb .= '"content_type": "product",';
					$fb .= '"value": "'.$value.'",';
					$fb .= '"num_items": "'.count($ids).'",';
					$fb .= '"contents": ['.$productsFb.'],';
				$fb .= '};';
				
				if(UTools::getSetting('debug') == 'Y'){
					$fb .= 'console.log("EC: Check facebook InitiateCheckout");';
					$fb .= 'console.log(fbparam);';
				}
				
				$fb .= 'if (typeof fbq == "function"){ fbq("track", "InitiateCheckout", fbparam); } else { console.log("EC Warning: fbq() not function"); };';
			}
			
			
			Tools::addScriptToHead($google.$fb);
		}
	}
}
?>