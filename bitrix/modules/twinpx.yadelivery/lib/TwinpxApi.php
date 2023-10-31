<?php

namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Fuser,
	Bitrix\Sale\Basket,
	Bitrix\Main\Loader,
	Bitrix\Main\Web\Json,
	Bitrix\Sale\Discount,
	Bitrix\Main\Text\Encoding;

use Twinpx\Yadelivery\TwinpxConfigTable;

Loader::includeModule('sale');
Loc::loadMessages(__FILE__);

//класс для работы с api доставки
class TwinpxApi
{
	public $default;
	static $url = 'https://b2b-authproxy.taxi.yandex.net'; //API Яндекс боевой
	static $demourl = 'https://b2b.taxi.tst.yandex.net'; //API Яндекс тестовый
	static $module_id = 'twinpx.yadelivery';

	function __construct()
	{
		//return TRUE;
	}

	public static function SetLogs($data = array(), $type = 'DEBUG', $object = false)
	{
		global $USER;
		$check = TwinpxConfigTable::getByCode('Enable_Logs'); //получаем флаг логирование
		if ($check === "Y") {
			\CEventLog::Add(
				array(
					'SEVERITY'     => $type,
					'AUDIT_TYPE_ID' => 'TWINPX_LOGS',
					'MODULE_ID'    => self::$module_id,
					'ITEM_ID'      => $object,
					'USER_ID'      => ($USER->GetID()) ? $USER->GetID() : '',
					'DESCRIPTION'  => print_r($data, true)
				)
			);
		}
	}

	//получаем API ключ
	public static function GetApiOAuth()
	{
		$auth = FALSE;
		$result = self::GetValue('OAuth');

		if ($result) {
			$auth = $result;
		}

		return $auth;
	}

	//получаем Platform_id
	public static function GetPlatformId()
	{
		$platform = FALSE;
		$result = self::GetValue('PlatformId');
		if ($result) {
			$platform = $result;
		}
		return $platform;
	}

	//получаем значение из таблицу по ключу
	public static function GetValue($code)
	{
		if (!$code)
			return;
		$obConfig = TwinpxConfigTable::getList(array('select' => array('VALUE'), 'filter' => array('CODE' => $code)));
		if ($arResult = $obConfig->fetch()) {
			$return = $arResult['VALUE'];
		}

		return $return;
	}

	//подготовка массива для примерной цены
	public static function PrepareDataCalculate($arData)
	{
		if (empty($arData))
			return; //нет данные
		$insurance = ($arData['INSURANCE'] == 'Y') ? true : false; //отключение страховки
		$default = TwinpxConfigTable::GetAllOptions(); //значение по умолчание
		$items   = self::GetItems(false, $insurance);

		if (empty($items['items'])) {
			$items['price'] = intval($arData['PRICE'] * 100);
			$items['weight'] = intval($default['Volume']);
		}

		$data    = array(
			"destination"         => ["address" => $arData['CITY']],
			"payment_method"      => $arData['PAYMENT_METHOD'],
			"tariff"              => $arData['TARIFF'],
			"source"              => ["platform_station_id" => $default['PlatformId']],
			"client_price"        => $items['price'],
			"total_assessed_price" => ($insurance) ? 0 : $items['price'],
			"total_weight"        => $items['weight']
		);
		return $data;
	}

	//подготовка массива для отправки в яндекс
	public static function PrepareData($arData, $order_id = FALSE, $isAdmin = FALSE)
	{
		if (empty($arData))
			return; //нет данные

		$session = \Bitrix\Main\Application::getInstance()->getSession();
		$insurance = ($arData['INSURANCE'] == 'Y') ? true : false; //отключение страховки
		$default      = TwinpxConfigTable::GetAllOptions(); //значение по умолчание
		$items        = self::GetItems($order_id, $insurance);
		$operator_request_id = ($order_id > 0) ? uniqid($order_id . "_") : uniqid();
		$full_address = '';
		$custom_location = '';
		$platform_station = '';
		$delivery_cost = 0;


		if ($arData['PVZ_ID']) {
			$type = 1;
			$last_mile_policy = 'self_pickup';

			$platform_station = array("platform_id" => $arData['PVZ_ID']);
			$full_address = $arData['FULL_ADDRESS'];
		} else {
			$type = 2;
			$last_mile_policy = 'time_interval';

			if ($arData['CITY']) {
				$full_address .= $arData['CITY'];
			}

			//если есть полный адрес
			if ($arData['FULL_ADDRESS']) {
				$full_address .= ', ' . $arData['FULL_ADDRESS'];
			} else {

				if ($arData['STREET'])
					$full_address .= ', ' . GetMessage("TWINPX_PREFIX_STREET") . $arData['STREET'];

				if ($arData['HOME'])
					$full_address .= ', ' . GetMessage("TWINPX_PREFIX_HOME") . $arData['HOME'];

				if ($arData['CORPS'])
					$full_address .= ', ' . GetMessage("TWINPX_PREFIX_CORPS") . $arData['CORPS'];

				if ($arData['APARTAMENT'])
					$full_address .= ', ' . GetMessage("TWINPX_PREFIX_AP") . $arData['APARTAMENT'];
			}

			$custom_location = array("details" => array("full_address" => $full_address));

			//если есть комментарий
			if ($arData['COMMENT']) {
				$custom_location["details"] += array("comment" => $arData['COMMENT']);
			}
		}

		//если есть фик. цена
		if ($arData['FIX_PRICE'] >= 0 && $arData['FIX_PRICE'] !== FALSE) {
			if ($isAdmin) {
				$delivery_cost = $arData['FIX_PRICE'];
			} elseif ($arData['PAYMENT'] == 'already_paid') {
				$delivery_cost = 0;
			} else {
				$delivery_cost = $arData['FIX_PRICE'];
			}
		}

		//если есть массив с габаритами, передаем для расчета габарита коробки
		if ($arData['LENGTH'] && $arData['WIDTH'] && $arData['HEIGHT']) {
			//unset($data['places'][0]['physical_dims']['predefined_volume']);
			//$data['places'][0]['physical_dims']['dx'] = intval($arData['LENGTH']);
			//$data['places'][0]['physical_dims']['dy'] = intval($arData['HEIGHT']);
			//$data['places'][0]['physical_dims']['dz'] = intval($arData['WIDTH']);
			$minDimensionPack = array(intval($arData['LENGTH']), intval($arData['HEIGHT']), intval($arData['WIDTH'])); //если заданы все размеры вручную
		} elseif (!empty($items['dimensions'])) {
			//$minDimensionPack = self::CalculateDimension($items['dimensions']);
			$minDimensionPack = array(30, 38, 20);
		}

		//подготовка массива
		$data = array(
			"last_mile_policy" => $last_mile_policy,
			"info" => array(
				"operator_request_id"	=> mb_substr($operator_request_id, 0, 20), //обрезаем до 20 символов, ограничение в яндекс
				"referral_source" 		=> "1Cbitrix_2px_ndd",
			),
			"source" => array(
				"type" => 1,
				"platform_station" 		=> array(
					"platform_id" 		=> self::GetPlatformId()
				)
			),
			"destination" => array(
				"type" => $type,
			),
			"items" => $items['items'],
			"places" => array(
				array(
					//"description"=> '',//$default['Description'],
					"barcode"    => $session->get('YD_BARCODE'),
					"physical_dims" => array(
						//"predefined_volume" => intval($items['volume']),
						"weight_gross" 	=> intval($items['weight']),
						"dx" => ($minDimensionPack[0]) ? $minDimensionPack[0] : 0,
						"dy" => ($minDimensionPack[1]) ? $minDimensionPack[1] : 0,
						"dz" => ($minDimensionPack[2]) ? $minDimensionPack[2] : 0,
					),
				)
			),
			"billing_info" => array(
				"payment_method" => $arData['PAYMENT'],
				"delivery_cost" => round($delivery_cost * 100)
			),
			"recipient_info" 	=> array(
				"first_name"	=> $arData['FIO'],
				//"first_name"	=> $arData['FIO'][0],
				//"last_name" 	=> $arData['FIO'][1],
				"phone"     	=> $arData['PHONE'],
				"email"			=> $arData['EMAIL']
			),
		);


		//если ПВЗ
		if ($platform_station) {
			$data['destination']['platform_station'] = $platform_station;
		}

		//если курьером
		if ($custom_location) {
			$data['destination']['custom_location'] = $custom_location;
		}

		//завпросы из админки
		if ($isAdmin) {
			//
		}

		//сохраняем сессий
		$session->set('YD_FULL_ADDRESS', $full_address); //передаем полный адрес в сессию, для записи в БД
		$session->set('YD_DELIVERY_COST', $delivery_cost); //передаем delivery_cost в сохранение заказа

		return $data;
	}

	public static function GetItems($order_id = FALSE, $insurance = FALSE)
	{
		$session = \Bitrix\Main\Application::getInstance()->getSession();
		$items = array();
		$totalVolume = 0;
		$totalPrice = 0;
		$totalWeight = 0;
		$deliveryPrice = false;
		$arData = array();
		$totalDimensions = array();
		$default = TwinpxConfigTable::GetAllOptions();
		if ($order_id > 0) //если передан ID заказа, получаем список товароа из заказа
		{
			$obBasket = Basket::getList(array('filter' => array('ORDER_ID' => $order_id)));
			while ($arItem = $obBasket->Fetch()) {
				$arData[] = $arItem;
				$ids[] = $arItem['PRODUCT_ID'];
			}
		} else //получаем товары из корзины покупателя
		{
			$basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID)->getOrderableItems(); //только доступные товары к покупке
			if (intval($basket->getQuantityList()) > 0) {
				$basket->refreshData(array('PRICE', 'COUPONS'));
				$fuser       	= new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true));
				$discounts      = \Bitrix\Sale\Discount::buildFromBasket($basket, $fuser);
				$obDiscount 	= $discounts->calculate();
				if (!$obDiscount->isSuccess()) {
					TwinpxApi::SetLogs($obDiscount->getErrorMessages(), '', 'GetItems.discountCalculate'); //
				} else {
					$itemDiscount = $obDiscount->getData();
					if (!empty($itemDiscount['BASKET_ITEMS'])) {
						$resultApply = $basket->applyDiscount($itemDiscount['BASKET_ITEMS']);
						if (!$resultApply->isSuccess()) {
							TwinpxApi::SetLogs($resultApply->getErrorMessages(), '', 'GetItems.applyDiscount'); //
						}
					}
				}
				foreach ($basket as $basketItem) {
					$product = $basketItem->getFieldValues();
					$arData[] = $product;
					$ids[] = $product['PRODUCT_ID'];
				}
			} else {
				//если нет товары в конзине делаем исключение
				return array('items' => $items, 'price' => $totalPrice, 'volume' => $totalVolume, 'weight' => $totalWeight, 'delivery' => $deliveryPrice);
			}
		}

		//получаем список свойств
		$arrSelect = array();
		foreach ($default as $key => $val) {
			//свойства для артикула
			$posA = strpos($key, 'ArticleProduct');
			if ($posA === false) {
				//
			} else {
				$arrSelect[] = $val;
			}
			//свойства для баркода
			$posB = strpos($key, 'BarcodeProduct');
			if ($posB === false) {
				//
			} else {
				$arrSelect[] = $val;
			}
		}
	
		//получаем значение для артикула и баркод, если указаны
		if (!empty($ids)) {
			////получаю по ID элементы, для получение id инфоблока
			$arSelect = array("ID", "IBLOCK_ID");
			$res = \CIBlockElement::GetList(array(), array("ID" => $ids), false, false, $arSelect);
			while ($ob = $res->Fetch()) {
				$elm[$ob['IBLOCK_ID']][] = $ob['ID'];
			}
			//получаем свосйства элементов
			if(!empty($elm)) {
				foreach($elm as $ib => $ids){
					$res = \CIBlockElement::GetList(array(), array("ID" => $ids, "IBLOCK_ID" => $ib), false, false, array_merge($arSelect, $arrSelect));
					while ($ob = $res->Fetch()) {
						$properyProduct[$ob['ID']] = $ob;
					}
				}
			}
		}

		//
		$place_barcode = self::GetPlaceBarcode($order_id);
		if (!empty($arData)) {
			foreach ($arData as $k => $data) {
				//проверяем товар или ТП
				$item       = $properyProduct[$data['PRODUCT_ID']];

				$keyArtikle = $default['ArticleProduct_' . $item['IBLOCK_ID']]; //получаем свойство для артикула
				$keyBarcode = $default['BarcodeProduct_' . $item['IBLOCK_ID']]; //получаес свойсво для баркода

				$article    = ($item[$keyArtikle . '_VALUE']) ? $item[$keyArtikle . '_VALUE'] : '_' . $data['PRODUCT_ID']; //если нет передаем ID товара
				$barcode    = ($item[$keyBarcode . '_VALUE']) ? $item[$keyBarcode . '_VALUE'] : '_' . $data['PRODUCT_ID']; //если нет передаем ID товара

				$volume     = ($default['Volume']) ? $default['Volume'] : 0; //а настройки отключен, переведен на габариты

				$dx			= ($default['dx'] <> '') ? ceil($default['dx'] / 10) : 0;
				$dy			= ($default['dy'] <> '') ? ceil($default['dy'] / 10) : 0;
				$dz			= ($default['dz'] <> '') ? ceil($default['dz'] / 10) : 0;

				$weight     = ($default['Weight'] <> '') ? ceil($default['Weight']) : 0;
				$price      = $data['PRICE'];
				$quantity   = intval($data['QUANTITY']);

				//работа с еденицы измерение
				$getFraction =  explode(".", $data['QUANTITY']);
				$intFraction = intval($getFraction[1]); //int

				//если количество не целое
				if ($intFraction !== 0) {
					$quantity = 1;
					$price = $price * $data['QUANTITY'];
				}

				//обём и размеры 1 товара
				$arSizes    = (is_array($data['DIMENSIONS'])) ? $data['DIMENSIONS'] : unserialize($data['DIMENSIONS']); //в мм
				//если есть все размеры
				if ($arSizes['WIDTH'] and $arSizes['HEIGHT'] and $arSizes['LENGTH']) {
					$volume = ($arSizes['WIDTH'] * 0.1) * ($arSizes['HEIGHT'] * 0.1) * ($arSizes['LENGTH'] * 0.1); //больше не будет использоватея

					$dx     = ($arSizes['LENGTH']) ? ceil($arSizes['LENGTH'] / 10) : 0;
					$dy     = ($arSizes['HEIGHT']) ? ceil($arSizes['HEIGHT'] / 10) : 0;
					$dz     = ($arSizes['WIDTH']) ? ceil($arSizes['WIDTH'] / 10) : 0;
				}

				if ($data['WEIGHT'] > 0) {
					$weight = $data['WEIGHT']; //в гр
				}

				$items[] = array(
					"count"          => $quantity,
					"name"           => ($data['NAME']) ? $data['NAME'] : GetMessage("TWINPX_TOVAR") . $data['PRODUCT_ID'],
					"article"        => $article,
					"barcode"        => $barcode,
					"billing_details"     => array(
						"unit_price"         => ceil($price * 100),
						"assessed_unit_price" => ($insurance) ? 0 : ceil($price * 100)
					),
					"physical_dims"       => array(
						"predefined_volume" => ceil($volume),
						"dx" => $dx,
						"dy" => $dy,
						"dz" => $dz,
					),
					"place_barcode"  => $place_barcode
				);

				//передаем для расчета коробки если только если все размеры заполнены
				if ($dx > 0 && $dy > 0 && $dz > 0) {
					for ($s = 0; $s < $quantity; $s++) {
						$totalDimensions[] = [$dx, $dy, $dz];
					}
				}
				$totalPrice += ceil($price * 100) * $quantity; //
				$totalVolume += ceil($volume) * $quantity;
				$totalWeight += ceil($weight) * $quantity;
			}
		}
		return array('items' => $items, 'price' => $totalPrice, 'volume' => $totalVolume, 'weight' => $totalWeight, 'delivery' => $deliveryPrice, 'dimensions' => $totalDimensions);
	}

	//длина не более 30 символов 
	public static function GetPlaceBarcode($order_id = false, $len = 30)
	{
		$session = \Bitrix\Main\Application::getInstance()->getSession();
		$default = TwinpxConfigTable::GetAllOptions();

		$strBarcode = $default['Barcode'];
		$order = ($order_id) ? intval($order_id) : '';
		$uniq = rand(100000, 999999);
		$date = date('ymd');
		$time = date('Hi');
		//translate		
		$strBarcode = \Cutil::translit($strBarcode, LANGUAGE_ID, array("change_case" => false));
		if (strlen($strBarcode) > ($len - strlen($order) + 1)) //если префикс длинее 29 символов
		{
			if ($order > 0) { //если имеем номер заказа
				$strOrder = intval(strlen($order) + 1);
				$cutBarcode = mb_substr($strBarcode, 0, ($len - $strOrder)); //обрезаем префикс
				$barcode = $cutBarcode . '_' . $order; //добавляем № заказа
			} else {
				$arBarcode[] = mb_substr($strBarcode, 0, 11);
				//$arBarcode[] = $date; //убираем для сокращение
				//$arBarcode[] = $time; //убираем для сокращение
				$arBarcode[] = $uniq;
				$barcode = implode("_", $arBarcode);
			}
		} else //собираем все данные в строку и обрезаем до нужной длины
		{
			$arBarcode[] = mb_substr($strBarcode, 0, 11);
			if ($order > 0) //если есть номер заказа
			{
				$arBarcode[] = $order;
				//$arBarcode[] = $date; //убираем для сокращение
				//$arBarcode[] = $time; //убираем для сокращение
				$arBarcode[] = $uniq;
			} else {
				//$arBarcode[] = $date; //убираем для сокращение
				//$arBarcode[] = $time; //убираем для сокращение
				$arBarcode[] = $uniq;
			}

			$barcode = implode("_", $arBarcode);
		}
		$return = mb_substr($barcode, 0, $len); //обрезаем до 30 символов
		$session->set("YD_BARCODE", $return); //запоминаем в сессию

		return $return; //возвращаем баркод
	}

	//вывод офферов  админке ()
	public static function ShowOfferAdmin($arResult, $order_id = false)
	{
		if (empty($arResult))
			return;

		foreach ($arResult as $interval => $offers) {
			if (count($offers) > 1) {
				foreach ($offers as $offer) {
					$arr[] = array(
						'offer_id'	=> $offer['offer_id'],
						'expire'  	=> $offer['expires_at'],
						'price'   	=> $offer['offer_details']['pricing'],
						'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
						'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
						'interval'	=> $interval
					);
				}
			} else {
				$offer = $offers[0];
				$arr[] = array(
					'offer_id'	=> $offer['offer_id'],
					'expire' 	=> $offer['expires_at'],
					'price'   	=> $offer['offer_details']['pricing'],
					'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
					'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
					'interval'	=> $interval
				);
			}
		}

		//отбираем одинаковые результаты
		foreach ($arr as $o) {
			$out[md5(serialize($o['delivery']))] = $o;
		}

		$html = '';
		if (!empty($out)) {
			$html = '<div class="yd-popup-offers__wrapper">';
			foreach ($out as $offer) {
				if (strlen($offer['price']) < 1) continue;

				$startDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['start']), "DD.MM.YYYY HH:MI:SS");
				$endDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['end']), "DD.MM.YYYY HH:MI:SS");

				$formatDate = FormatDate("j F Y", $startDate);

				$formatTimeStart = FormatDate("H:i", $startDate); //форматирование начало 
				$formatTimeEnd = FormatDate("H:i", $endDate); //форматирование конец

				$dataJson = array(
					'order_id' => $order_id,
					'offer_id' => $offer['offer_id'],
					'offer_expire' => $offer['expire'],
					'interval' => trim($offer['interval']),
					'price' => trim($offer['price'])
				);

				$html .= '
                	<div class="yd-popup-offers__item" data-object=\'' . \Bitrix\Main\Web\Json::encode($dataJson) . '\'>
                  		<b class="yd-popup-offers__date"><i style="background-image: url(\'/bitrix/images/twinpx.yadelivery/calendar.svg\')"></i>' . $formatDate . '</b>
				    	<span class="yd-popup-offers__time"><i style="background-image: url(\'/bitrix/images/twinpx.yadelivery/clock.svg\')"></i>c ' . $formatTimeStart . ' ' . GetMessage("TWINPX_DO") . $formatTimeEnd . '</span>
				    	<b class="yd-popup-offers__price">' . $offer['price'] . '</b>
				    	<a href="" class="ui-btn ui-btn-sm ui-btn-primary">' . GetMessage("TWINPX_VYBRATQ") . '</a>
				  	</div>
                ';
			}
			$html .= "</div>";
		} else {
			$html .= '<div class="yd-popup-error__message"><i style="background-image: url(/bitrix/images/twinpx.yadelivery/danger.svg)"></i>' . GetMessage("TWINPX_NET_DOSTUPNYH_INTERV") . '</div>';
		}

		return $html;
	}

	//вывод офферов в корзине ()
	public static function ShowOffer($arResult)
	{
		if (empty($arResult))
			return;

		foreach ($arResult as $interval => $offers) {
			if (count($offers) > 1) {
				foreach ($offers as $offer) {
					$arr[] = array(
						'offer_id'	=> $offer['offer_id'],
						'expire'  	=> $offer['expires_at'],
						'price'   	=> $offer['offer_details']['pricing'],
						'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
						'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
						'interval'	=> $interval
					);
				}
			} else {
				$offer = $offers[0];
				$arr[] = array(
					'offer_id'	=> $offer['offer_id'],
					'expire' 	=> $offer['expires_at'],
					'price'   	=> $offer['offer_details']['pricing'],
					'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
					'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
					'interval'	=> $interval
				);
			}
		}

		//отбираем одинаковые результаты
		foreach ($arr as $o) {
			$out[md5(serialize($o['delivery']))] = $o;
		}

		/**
		 * 
		 * @var todo
		 * компонент для возможность кастомизации
		 * 
		 */
		$html = '';
		if (!empty($out)) {
			$html = '<div class="yd-popup-offers__wrapper">';
			foreach ($out as $offer) {
				if (strlen($offer['price']) < 1) continue;

				$startDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['start']), "DD.MM.YYYY HH:MI:SS");
				$endDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['end']), "DD.MM.YYYY HH:MI:SS");

				$formatDate = FormatDate("j F Y", $startDate);

				$formatTimeStart = FormatDate("H:i", $startDate); //форматирование начало 
				$formatTimeEnd = FormatDate("H:i", $endDate); //форматирование конец

				$data = array(
					'offer_id' 		=> $offer['offer_id'],
					'offer_price'	=> $offer['price'],
					'offer_expire'	=> $offer['expire']
				);
				$jsonData = \Bitrix\Main\Web\Json::encode($data); //передаем данные и

				$html .= '
                	<div class="yd-popup-offers__item" data-json=\'' . $jsonData . '\'>
                		<div class="yd-popup-offers__info">
	                    	<span class="yd-popup-offers__date"><i style="background-image: url(/bitrix/images/' . self::$module_id . '/pvz-calendar.svg)"></i>' . $formatDate . '</span>
	                    	<span class="yd-popup-offers__time"><i style="background-image: url(/bitrix/images/' . self::$module_id . '/pvz-clock.svg)"></i>c ' . $formatTimeStart . ' ' . GetMessage("TWINPX_DO") . $formatTimeEnd . '</span>
	                  	</div>
	                	<b class="yd-popup-offers__price">' . $offer['price'] . '</b>
	                	<a href="#" class="ui-btn ui-btn-sm ui-btn-primary">' . GetMessage("TWINPX_VYBRATQ1") . '</a>
                	</div>
                ';
			}
			$html .= "</div>";
		} else {
			$html .= '<div class="yd-popup-offers__wrapper">' . GetMessage("TWINPX_NET_DOSTUPNYH_INTERV") . '</div>';
		}

		return $html;
	}

	public static function ShowOfferJson($arResult, $pvzId = FALSE, $orderId = FALSE)
	{
		if (empty($arResult))
			return;

		$session = \Bitrix\Main\Application::getInstance()->getSession();
		$data = array();

		if ($pvzId) {
			$cost = ($session->has('YD_PICKUP_PRICE')) ? $session->get('YD_PICKUP_PRICE') : FALSE;
			foreach ($arResult as $offers) {
				$arr[] = array(
					'offer_id'	=> $offers['offer_id'],
					'expire' 	=> $offers['expires_at'],
					'price'   	=> $offers['offer_details']['pricing_total'],
					'delivery'  => array('start' => $offers['offer_details']['delivery_interval']['min'], 'end'  => $offers['offer_details']['delivery_interval']['max']),
					'pickup'    => array('start' => $offers['offer_details']['pickup_interval']['min'], 'end'  => $offers['offer_details']['pickup_interval']['max']),
				);
			}
		} else {
			$cost = ($session->has('YD_CURIER_PRICE')) ? $session->get('YD_CURIER_PRICE') : FALSE;
			foreach ($arResult as $interval => $offers) {
				if (count($offers) > 1) {
					foreach ($offers as $offer) {
						$arr[] = array(
							'offer_id'	=> $offer['offer_id'],
							'expire'  	=> $offer['expires_at'],
							'price'   	=> $offer['offer_details']['pricing_total'],
							'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
							'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
							'interval'	=> $interval,
						);
					}
				} else {
					$offer = $offers[0];
					$arr[] = array(
						'offer_id'	=> $offer['offer_id'],
						'expire' 	=> $offer['expires_at'],
						'price'   	=> $offer['offer_details']['pricing_total'],
						'delivery'  => array('start' => $offer['offer_details']['delivery_interval']['min'], 'end'  => $offer['offer_details']['delivery_interval']['max']),
						'pickup'    => array('start' => $offer['offer_details']['pickup_interval']['min'], 'end'  => $offer['offer_details']['pickup_interval']['max']),
						'interval'	=> $interval,
					);
				}
			}
		}

		//если было расчитано наценка
		if ($session->has('MARGIN_VALUE') && $session->get('MARGIN_VALUE') >= 0) {
			$cost = $session->get('MARGIN_VALUE');
		}

		//отбираем одинаковые результаты
		foreach ($arr as $o) {
			$out[md5(serialize($o['delivery']))] = $o;
		}

		if (!empty($out)) {
			foreach ($out as $offer) {
				if (strlen($offer['price']) < 1) continue;
				if ($offer['price'] === '0 ') continue;
				
				$startDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['start']), "DD.MM.YYYY HH:MI:SS");
				$endDate = MakeTimeStamp(self::PrepareDataTime($offer['delivery']['end']), "DD.MM.YYYY HH:MI:SS");

				$formatDate = FormatDate("j F Y", $startDate);

				$formatTimeStart = FormatDate("H:i", $startDate); //форматирование начало 
				$formatTimeEnd = FormatDate("H:i", $endDate); //форматирование конец
				
				//коректировка время доставки
				if($pvzId){
					if(intval(FormatDate("H", $startDate)) < 10) //если время меньше 10 часа
					$formatTimeStart = '10:00';
				}

				$data = array(
					'offer_id' 		=> $offer['offer_id'],
					'offer_price'	=> $offer['price'],
					'offer_expire'	=> $offer['expire'],
					'offer_pvz'		=> $pvzId,
					'type' 			=> ($pvzId) ? "pickup" : "curier",
					'cost'			=> round($cost, 2),
					'email'			=> $session->get('BAYER_EMAIL')
				);
				//если передан ID заказа
				if ($orderId) {
					$data['order_id'] .= $orderId;
				}

				$jsonData = \Bitrix\Main\Web\Json::encode($data); //передаем данные

				$output[] = array(
					"timestamp" => $startDate,
					"json" => $jsonData,
					"date" => $formatDate,
					"time" => ($pvzId) ? $formatTimeStart : GetMessage('TWINPX_FROM') . $formatTimeStart . GetMessage('TWINPX_TO') . $formatTimeEnd,
					"price" => ($cost !== FALSE && $orderId === FALSE) ? \CCurrencyLang::CurrencyFormat($cost, "RUB") : \CCurrencyLang::CurrencyFormat($offer['price'], "RUB")
				);
			}
			//сортировка по дате
			usort($output, function ($a, $b) {
				return ($a['timestamp'] - $b['timestamp']);
			});
		} else {
			$output = GetMessage("TWINPX_NET_DOSTUPNYH_INTERV");
		}
		$session->remove('BAYER_EMAIL');
		return $output;
	}

	public static function GenerateSchedule($arParams)
	{
		if (empty($arParams)) return;

		$arResult = array();
		$week = array(
			1 => GetMessage("TWINPX_MONDAY"),
			2 => GetMessage("TWINPX_TUESDAY"),
			3 => GetMessage("TWINPX_WEDNESDAY"),
			4 => GetMessage("TWINPX_THURSDAY"),
			5 => GetMessage("TWINPX_FRIDAY"),
			6 => GetMessage("TWINPX_SATURDAY"),
			7 => GetMessage("TWINPX_SUNDAY")
		);

		foreach ($arParams as $day) {
			$days = array();
			foreach ($day['days'] as $d) {
				$days[] = $week[$d];
			}

			$from = sprintf("%02d", $day['time_from']['hours']) . ':' . sprintf("%02d", $day['time_from']['minutes']);
			$to = sprintf("%02d", $day['time_to']['hours']) . ':' . sprintf("%02d", $day['time_to']['minutes']);

			$arResult[] = implode(", ", $days) . ': ' . $from . '-' . $to;
		}

		return "<div>" . GetMessage("TWINPX_WORK_TIME") . implode(". ", $arResult) . "</div>";
	}

	//deprecate
	public static function GenerateInterval($location = FALSE)
	{
		//Local Format
		//$days[] = strtotime(" + 3 day");
		if ($location['CODE'] == '0000073738') { //если это Москва
			$days[] = strtotime("+1 day");
			$days[] = strtotime("+2 day");
			$days[] = strtotime("+3 day");
			foreach ($days as $day) {
				$start1 = date('d.m.Y 09:00', $day);
				$end1 = date('d.m.Y 18:00', $day);

				$start2 = date('d.m.Y 14:00', $day);
				$end2  = date('d.m.Y 22:00', $day);

				$start3 = date('d.m.Y 19:00', $day);
				$end3  = date('d.m.Y 23:59', $day);

				$interval[] = array(
					'from' => strtotime($start1),
					'to'  => strtotime($end1),
					'fformat' => $start1,
					'tformat' => $end1
				);
				$interval[] = array(
					'from' => strtotime($start2),
					'to'  => strtotime($end2),
					'fformat' => $start2,
					'tformat' => $end2
				);
				$interval[] = array(
					'from' => strtotime($start3),
					'to'  => strtotime($end3),
					'fformat' => $start3,
					'tformat' => $end3
				);
			}
		} else {
			$days[] = strtotime("+1 day");
			$days[] = strtotime("+2 day");
			$days[] = strtotime("+3 day");
			foreach ($days as $day) {
				$start1 = date('d.m.Y 09:00', $day);
				$end1 = date('d.m.Y 22:00', $day);

				$interval[] = array(
					'from' => strtotime($start1),
					'to'  => strtotime($end1),
					'fformat' => $start1,
					'tformat' => $end1
				);
			}
		}

		return $interval;
	}

	public static function GetInterval($address = FALSE)
	{
		if (!$address) return;
		$interval = array();
		$uri = "/api/b2b/platform/offers/info/";

		$query = array("station_id" => self::GetPlatformId(), "send_unix" => true, "full_address" => $address);
		$result = self::requestGet($uri, $query);
		
		if ($result['SUCCESS']) {
			$interval = array_slice($result['DATA'], 0, 12); //обрезаем до 12 интервалов
		}
		return $interval;
	}

	public static function PrepareDataTime($utc)
	{
		$session = \Bitrix\Main\Application::getInstance()->getSession();
		$utcTZ = new \DateTimeZone("UTC");
		if ($session->has('TIMEZONE')) {
			$timeZone = $session->get('TIMEZONE');
		} else {
			$timeZone = date('O'); //по умолчание часовой пояс сервера
		}

		$dt = new \DateTime($utc, $utcTZ); //указываем что время UTC
		//$tz = new \DateTimeZone('Europe/Moscow'); // or whatever zone you're after
		$tz = new \DateTimeZone($timeZone); //конвертируем по зоне

		$dt->setTimezone($tz);

		return $dt->format('d.m.Y H:i:s'); //P для отладки
	}

	//метод для расчета мин. упаковки с товарами
	static function CalculateDimension($allDimension = array())
	{
		if (empty($allDimension)) return;

		if (count($allDimension) < 2) return $allDimension[0]; //если только 1 товар

		$firstDimension = array_splice($allDimension, 0, 2);
		$resultCalc = self::CalculateDimensionPack($firstDimension);

		if (!empty($allDimension)) {
			foreach ($allDimension as $i => $dimension) {
				$dimMerge = array($resultCalc, $dimension);
				$calc = self::CalculateDimensionPack($dimMerge); //считаем по 2 размера
				$resultCalc = $calc;
			}
		}

		return $resultCalc;
	}


	static function CalculateDimensionPack($arDimension = array())
	{
		//https://stackoverflow.com / a / 49268419
		//1. Find total volume
		$volume     = 0;
		//2. Find WHD ranges
		$lenthRange = array();
		$widthRange = array();
		$heightRange = array();
		foreach ($arDimension as $dimension) {
			//list($width, $height, $depth) = explode(',', $dimension);
			$length = $dimension[0];
			$width = $dimension[1];
			$height = $dimension[2];
			$volume += $width * $height * $length;

			$lenthRange[] = $length;
			$widthRange[] = $width;
			$heightRange[] = $height;
		}

		//3. Order the WHD ranges
		sort($lenthRange);
		sort($widthRange);
		sort($heightRange);

		//echo 'Volume: '.$volume.' < br />';
		//echo 'Lenth Range: '.implode(', ', $lenthRange).' < br />';
		//echo 'Width Range: '.implode(', ', $widthRange).' < br />';
		//echo 'Height Range: '.implode(', ', $heightRange).' < br />';

		//4. Figure out every combination with WHD
		$lenthCombination = array();
		$widthCombination = array();
		$heightCombination = array();

		$lenthCombination = self::DimensionPackCombination($lenthRange);
		$widthCombination = self::DimensionPackCombination($widthRange);
		$heightCombination = self::DimensionPackCombination($heightRange);

		//echo 'Lenth Combination: '.implode(', ', $lenthCombination).' < br />';
		//echo 'Height Combination: '.implode(', ', $heightCombination).' < br />';
		//echo 'Width Combination: '.implode(', ', $widthCombination).' < br />';

		$stacks = array();
		foreach ($lenthCombination as $length) {
			foreach ($widthCombination as $width) {
				foreach ($heightCombination as $height) {
					$v = $length * $width * $height;
					if ($v >= $volume) {
						$stacks[$v][$length + $width + $height] = array($length, $width, $height);
					}
				}
			}
		}

		ksort($stacks);

		foreach ($stacks as $i => $dims) {
			ksort($stacks[$i]);
			foreach ($stacks[$i] as $j => $stack) {
				rsort($stack);
				break;
			}
			break;
		}

		$firstAr = current($stacks); //первый элемент многоуровнего массива
		$finalAr = current($firstAr); //первый элемент со второго уровня

		return $finalAr;
		//echo '<pre>'.print_r($stacks, true).'</pre>';
	}

	static function DimensionPackCombination($list)
	{
		$combination = array();
		$total = pow(2, count($list));
		for ($i = 0; $i < $total; $i++) {
			$set = array();
			//For each combination check if each bit is set
			for ($j = 0; $j < $total; $j++) {
				//Is bit $j set in $i?
				if (pow(2, $j) & $i) $set[] = $list[$j];
			}

			if (empty($set) || in_array(array_sum($set), $combination)) {
				continue;
			}

			$combination[] = array_sum($set);
		}

		sort($combination);
		return $combination;
	}

	//форматируем адрес из массива
	public static function PrepareAddress($data = FALSE)
	{
		if (!$data) return;

		$full_address = '';
		if ($data['CITY'])
			$full_address .= $data['CITY'];

		if ($data['STREET'])
			$full_address .= ', ' . GetMessage("TWINPX_PREFIX_STREET") . $data['STREET'];

		if ($data['HOME'])
			$full_address .= ', ' . GetMessage("TWINPX_PREFIX_HOME") . $data['HOME'];

		if ($data['CORPS'])
			$full_address .= ', ' . GetMessage("TWINPX_PREFIX_CORPS") . $data['CORPS'];

		if ($data['APARTAMENT'])
			$full_address .= ', ' . GetMessage("TWINPX_PREFIX_AP") . $data['APARTAMENT'];

		return $full_address;
	}

	public static function GetOfferState($data)
	{
		$getQuery = array('request_id' => $data);
		$getState = TwinpxApi::requestGet('/api/b2b/platform/request/info', $getQuery);

		if ($getState['SUCCESS']) {
			$result = array(
				'STATUS'     => $getState['DATA']['state']['status'],
				'DESCRIPTION' => $getState['DATA']['state']['description']
			);
		}

		return $result;
	}

	public static function CancelOffer($data)
	{
		$getQuery = array('request_id' => $data);
		$getState = TwinpxApi::requestPost('/api/b2b/platform/request/cancel', $getQuery);

		if ($getState['SUCCESS']) {
			$result = array(
				'STATUS'     => $getState['DATA']['status'],
				'DESCRIPTION' => $getState['DATA']['description']
			);
		}

		return $result;
	}

	public static function GetBarcode($data)
	{
		$getQuery = array('request_ids' => array($data));
		$getState = TwinpxApi::requestPdf('/api/b2b/platform/request/generate-labels', $getQuery);

		if ($getState['SUCCESS']) {
			$result['SUCCESS'] = true;

			if ($getState['DATA']['PDF'] and file_exists($_SERVER["DOCUMENT_ROOT"] . $getState['DATA']['PDF'])) {
				$result['DATA'] .= '<a class="btn btn-default ui-btn" target="_blank" href="' . $getState['DATA']['PDF'] . '"> ' . GetMessage("TWINPX_SKACATQ") . '</a>';
			}
		}


		return $result;
	}

	public static function GetDocument($data)
	{
		$getQuery = array('new_requests' => 'false', 'request_ids' => $data);
		$getState = TwinpxApi::requestGetPdf('/api/b2b/platform/request/get-handover-act', $getQuery);

		if ($getState['SUCCESS']) {
			$result['SUCCESS'] = true;

			if ($getState['DATA']['PDF'] and file_exists($_SERVER["DOCUMENT_ROOT"] . $getState['DATA']['PDF'])) {
				$result['DATA'] .= '<a class="btn btn-default ui-btn" target="_blank" href="' . $getState['DATA']['PDF'] . '"> ' . GetMessage("TWINPX_DOWNLOAD_AKT") . '</a>';
			}
		}


		return $result;
	}

	/**
	 * 
	запросы в Яндекс
	 */
	public static function requestPost($uri, $data = array(), $oAuth = false)
	{
		$check = self::GetValue('Checkbox_Demo');
		$url = ($check === 'Y') ? self::$demourl : self::$url;
		$oAuth = ($oAuth) ? $oAuth : TwinpxApi::GetApiOAuth(); //если передан $oAuth (токен) вручную

		$result = array('SUCCESS' => false, 'DATA' => GetMessage("TWINPX_ERROR_POST"));
		$dataJson = \Bitrix\Main\Web\Json::encode($data);

		TwinpxApi::SetLogs($dataJson, '', 'requestPost.request'); //Log

		try {
			$path    = $url . $uri;
			$headers = array(
				"Authorization: Bearer " . $oAuth . "",
				"Content-Type: application/json"
			);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $path);
			curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);

			//for debug only!
			//curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp     = curl_exec($curl);
			if (!curl_errno($curl)) {
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$result['CODE'] = $http_code;
			}
			curl_close($curl);
			$response = json_decode($resp, TRUE); //json to array

			//если бронирование успешно прошла
			if ($response['request_id']) {
				TwinpxApi::SetLogs($resp, '', 'requestPost.response'); //Log
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = $response;
			}
			//если получили PVZ
			elseif (is_array($response['points'])) {
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					foreach($response['points'] as $i => &$val){
						$val['name'] = \Bitrix\Main\Text\Encoding::convertEncoding($val['name'], "UTF-8", LANG_CHARSET); //для кодировки windows-1251
						$val['address'] = \Bitrix\Main\Text\Encoding::convertEncoding($val['address'], "UTF-8", LANG_CHARSET); //для кодировки windows-1251
						$val['contact'] = \Bitrix\Main\Text\Encoding::convertEncoding($val['contact'], "UTF-8", LANG_CHARSET); //для кодировки windows-1251
					}
					unset($val);
					//$response = mb_convert_encoding($response, LANG_CHARSET, "UTF-8"); //для кодировки windows-1251
				} 
				$result['DATA'] = $response['points'];
			}
			//если получили офферы
			elseif ($response['offers']) {
				TwinpxApi::SetLogs($resp, '', 'requestPost.response'); //Log

				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = array($response['offers']);

				unset($_SESSION['JSON_ANSWER']); //обнуляем предыдущие результаты
				unset($_SESSION['JSON_REQUEST']); //обнуляем предыдущие результаты

				$_SESSION['JSON_REQUEST'][] = $dataJson;
				$_SESSION['JSON_ANSWER'][] = $response['offers'];
			}
			//если получили детальную ошибку
			elseif ($response['error_details']) {
				TwinpxApi::SetLogs($resp, '', 'requestPost.error'); //Log
				$result['SUCCESS'] = TRUE;

				$result['DATA'] = array('error' => $response);
			} else {
				//TwinpxApi::SetLogs($resp, '', 'requestPost.response'); //Log
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = $response;
			}
		} catch (Exception $e) {
			$result['DATA'] = GetMessage("REQUEST_SEND_QUERY");
		}

		return $result;
	}

	public static function requestGet($uri, $data = array())
	{
		$check = self::GetValue('Checkbox_Demo');
		$url = ($check === 'Y') ? self::$demourl : self::$url;

		$result = array('SUCCESS' => false, 'DATA' => GetMessage('TWINPX_ERROR_POST'));

		TwinpxApi::SetLogs(\Bitrix\Main\Web\Json::encode($data), '', 'requestGet.request'); //Log

		try {
			if (LANG_CHARSET != 'UTF-8') {
				$data = \Bitrix\Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, "UTF-8"); //кодировка в UTF-8
			}
			$getQuery = http_build_query($data);
			$path     = $url . $uri . '?' . $getQuery;

			$headers  = array(
				"Authorization: Bearer " . TwinpxApi::GetApiOAuth() . "",
				"Content-Type: application/json"
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $path);
			curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			//for debug only!
			//curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp     = curl_exec($curl);
			curl_close($curl);

			$response = json_decode($resp, TRUE);

			TwinpxApi::SetLogs($resp, '', 'requestGet.response'); //Log

			//обновление статуса
			if ($response['state']) {
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = $response;
			}

			//
			if ($response['status']) {
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = $response;
			}

			//если запрос было для интервалов
			if (!empty($response['offers'])) {
				$result['SUCCESS'] = TRUE;
				if (LANG_CHARSET != 'UTF-8') {
					$response = \Bitrix\Main\Text\Encoding::convertEncoding($response, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
				}
				$result['DATA'] = $response['offers'];
			}
		} catch (Exception $e) {
			$result['DATA'] = GetMessage('REQUEST_SEND_QUERY');
		}

		return $result;
	}

	public static function requestPdf($uri, $data = array())
	{
		$check = self::GetValue('Checkbox_Demo');
		$url = ($check === 'Y') ? self::$demourl : self::$url;

		$result = array('SUCCESS' => false, 'DATA' => GetMessage("TWINPX_ERROR_POST"));

		$dataJson = \Bitrix\Main\Web\Json::encode($data);

		TwinpxApi::SetLogs($dataJson, '', 'requestPdf.request'); //Log

		try {
			$path    = $url . $uri;
			$headers = array(
				"Authorization: Bearer " . TwinpxApi::GetApiOAuth() . "",
				"Content-Type: application/json"
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $path);
			curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);

			//for debug only!
			//curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp     = curl_exec($curl);
			curl_close($curl);
			$response = json_decode($resp, TRUE);

			TwinpxApi::SetLogs($response, '', 'requestPdf.response'); //Log

			if ($response['error_details']) {
				$result['SUCCESS'] = TRUE;
			} else {

				$result['SUCCESS'] = TRUE;

				if (\Bitrix\Main\IO\Directory::createDirectory(\Bitrix\Main\Application::getDocumentRoot() . '/upload/barcode/')) {
					//обработка полченного файла PDF
					$savePath = "/upload/barcode/" . $data['request_ids'][0] . ".pdf";
					$imagePath = "/upload/barcode/" . $data['request_ids'][0] . ".jpg";

					$result['DATA'] = array('PDF' => $savePath, 'IMG' => $imagePath);

					$filePdf = file_put_contents($_SERVER["DOCUMENT_ROOT"] . $savePath, $resp);
				}
			}
		} catch (Exception $e) {
			$result['DATA'] = GetMessage("REQUEST_SEND_QUERY");
		}

		return $result;
	}

	public static function requestGetPdf($uri, $data = array())
	{
		$check = self::GetValue('Checkbox_Demo');
		$url = ($check === 'Y') ? self::$demourl : self::$url;

		$result = array('SUCCESS' => false, 'DATA' => GetMessage("TWINPX_ERROR_POST"));

		if (LANG_CHARSET != 'UTF-8') {
			$data = \Bitrix\Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, "UTF-8"); //кодировка в UTF-8
		}
		$getQuery = http_build_query($data);

		TwinpxApi::SetLogs(\Bitrix\Main\Web\Json::encode($data), '', 'requestGetPdf.request'); //Log

		try {
			$path    = $url . $uri . '?' . $getQuery;
			$headers = array(
				"Authorization: Bearer " . TwinpxApi::GetApiOAuth() . "",
				"Content-Type: application/json"
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $path);
			curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			//for debug only!
			//curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp     = curl_exec($curl);
			curl_close($curl);


			$response = json_decode($resp, TRUE);
			if ($response['error_details']) {
				$result['SUCCESS'] = TRUE;
				TwinpxApi::SetLogs($response['error_details'], '', 'requestGetPdf.response'); //Log
			} else {

				$result['SUCCESS'] = TRUE;

				if (\Bitrix\Main\IO\Directory::createDirectory(\Bitrix\Main\Application::getDocumentRoot() . '/upload/akt/')) {
					//обработка полченного файла PDF
					$savePath = "/upload/akt/" . $data['request_ids'] . "_act.pdf";
					$result['DATA'] = array('PDF' => $savePath);
					$filePdf = file_put_contents($_SERVER["DOCUMENT_ROOT"] . $savePath, $resp);

					TwinpxApi::SetLogs($savePath, '', 'requestGetPdf.response'); //Log
				}
			}
		} catch (Exception $e) {
			$result['DATA'] = GetMessage("REQUEST_SEND_QUERY");
		}

		return $result;
	}

	public static function multiRequest($uri, $data, $interval)
	{
		$check = self::GetValue('Checkbox_Demo');
		$url = ($check === 'Y') ? self::$demourl : self::$url;

		$result = array('SUCCESS' => false, 'DATA' => GetMessage("TWINPX_ERROR_POST"));
		$error = TRUE;
		unset($_SESSION['JSON_ANSWER']);
		unset($_SESSION['JSON_REQUEST']);

		try {
			$multiple = curl_multi_init();
			$channels = array();

			TwinpxApi::SetLogs(\Bitrix\Main\Web\Json::encode($data), '', 'multiRequest.request'); //Log

			foreach ($interval as $i) {
				//прописываем интервалы
				$data['destination']['interval']['from'] = $i['from'];
				$data['destination']['interval']['to'] = $i['to'];
				$dataJson = \Bitrix\Main\Web\Json::encode($data); //создаем json

				$path     = $url . $uri;
				$headers  = array(
					"Authorization: Bearer " . TwinpxApi::GetApiOAuth() . "",
					"Content-Type: application/json"
				);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $path);
				curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);

				//for debug only!
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

				curl_multi_add_handle($multiple, $curl);

				$channels[$data['destination']['interval']['from']] = $curl;
				$_SESSION['JSON_REQUEST'][$data['destination']['interval']['from']] = $dataJson;
			}

			$active = null;
			do {
				$mrc = curl_multi_exec($multiple, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			while ($active && $mrc == CURLM_OK) {
				if (curl_multi_select($multiple) == -1) {
					continue;
				}
				do {
					$mrc = curl_multi_exec($multiple, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}

			$result = array();

			foreach ($channels as $int => $channel) {
				$res = json_decode(curl_multi_getcontent($channel), TRUE);

				//если есть офферы
				if ($res['offers']) {
					TwinpxApi::SetLogs(curl_multi_getcontent($channel), '', 'multiRequest.responseOffers'); //Log

					$result['SUCCESS'] = TRUE;
					if (LANG_CHARSET != 'UTF-8') {
						$res = \Bitrix\Main\Text\Encoding::convertEncoding($res, "UTF-8", LANG_CHARSET); //для кодировки windows-1251
					}
					$result['DATA'][$int] = $res['offers'];

					$_SESSION['JSON_ANSWER'][$int] = $res['offers'];

					$error = FALSE; //если есть хоть один ответ
				}

				//если есть ошибка при получение
				if ($res['error_details']) {
					TwinpxApi::SetLogs(curl_multi_getcontent($channel), '', 'multiRequest.responseError'); //Log

					$err_message[] = $res['error_details'];
				}

				//при ошибки 500
				if ($res['message']) {
					TwinpxApi::SetLogs(curl_multi_getcontent($channel), '', 'multiRequest.responseMessage'); //Log

					$err_message[] = $res['message'];
				}

				curl_multi_remove_handle($multiple, $channel);
			}

			curl_multi_close($multiple); //закрываем curl

		} catch (Exception $e) {
			$result['DATA'] = GetMessage("REQUEST_SEND_QUERY");
		}

		if ($error) {
			$result = array(
				'SUCCESS' => TRUE,
				'ERROR' => $err_message
			);
		}

		return $result;
	}
}
