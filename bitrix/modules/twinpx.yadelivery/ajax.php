<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use	Bitrix\Sale\Fuser,
	Bitrix\Sale\Basket,	
	Bitrix\Main\Loader,
	Bitrix\Main\Context,
	Bitrix\Main\Request,
	Bitrix\Main\Web\Json,
	Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Delivery\Services\Table,
	Bitrix\Sale\Location\Admin\LocationHelper;
	
use Twinpx\Yadelivery\TwinpxApi,
    Twinpx\Yadelivery\TwinpxConfigTable,
    Twinpx\Yadelivery\TwinpxOfferTable;


Loader::includeModule('location');

//init js lib
CJSCore::Init(array('twinpx_lib')); 
//init module
CModule::IncludeModule("twinpx.yadelivery");
CModule::IncludeModule("sale");

Loc::loadMessages(__FILE__);

$module_id = "twinpx.yadelivery";
$siteId = Context::getCurrent()->getSite();
$request = Context::getCurrent()->getRequest();
$session = Application::getInstance()->getSession();

if ($request->isPost()) {
    //сбрасываем оффер
    if ($request["action"] == 'reset') {
        if ($session->has('YD_OFFER_ID')) {
            $session->remove('YD_OFFER_ID');
        }
    }
                            
	//получаем список офферов
    if ($request["action"] == 'getOffer') {
        //parse_str($request["fields"], $fields); //метод не подходит когда есть 2 поле с одинаковых название
        parse_str($request['props'], $formProps); //поля из модальной окне
        //парсим строку
        $parse = explode('&', $request["fields"]);
        $fields = array();
        foreach ( $parse as $param ) {
            if (strpos($param, '=') === false) $param += '=';
            list($name, $value) = explode('=', $param, 2);
            $fields[urldecode($name)][] = urldecode($value);
        }

        if (LANG_CHARSET != 'UTF-8') {
            $fields    = \Bitrix\Main\Text\Encoding::convertEncoding($fields, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
            $formProps = \Bitrix\Main\Text\Encoding::convertEncoding($formProps, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
        }
        TwinpxApi::SetLogs($fields, '', 'getOffer.fields'); //
        //перебор значение
        if (!empty($fields)) {
            foreach ($fields as $name => $value) {
                if (count($value) > 1) {
                    $arFields[$name] = '';
                    foreach ($value as $v) {
                        if ($v != '') $arFields[$name] = $v; //берем значение которые заполнены только
                    }
                }
                else {
                    $arFields[$name] = $value[0];
                }
            }
        }
        $fields = $arFields;
        unset($arFields);

        $options   = TwinpxConfigTable::GetAllOptions();

        $pTypeId   = $fields['PERSON_TYPE']; //тип плательщика
        $thisPayID = $fields['PAY_SYSTEM_ID']; //
        $deliveryID= $fields['DELIVERY_ID']; //id доставки
        $payment   = FALSE;
        $emptyFields = array();
        $errors = "";
        $status = "N";
        $output = array("STATUS"=> $status);

        //получаем информацию по платежной системе
        if ($thisPayID > 0) {
            //если есть тип платежа
            if (strlen($options['Pay_'.$thisPayID]) > 0) {
                $payment = $options['Pay_'.$thisPayID];
            }
            else {
                $error[] = GetMessage('PaymentError');
            }
            $session->set('YD_PAY_ID', $thisPayID);
        }

        //$location = ($options['PropCity_'.$pTypeId]) ? CSaleLocation::GetByID($fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]], LANGUAGE_ID) : FALSE;
        $location = ($options['PropCity_'.$pTypeId]) ? TwinpxDelivery::GetLocationByCode($fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]]) : FALSE;
        
        //получаем свойствы заказа
        //личные данные
        $fio = ($options['PropFio_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropFio_'.$pTypeId]] : false;
        $phone = ($options['PropPhone_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropPhone_'.$pTypeId]] : false;
        $email = ($options['PropEmail_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropEmail_'.$pTypeId]] : "";
	
        //данные доставки
        $city  = ($location) ? $location : false;
        $street = ($options['PropStreet_'.$pTypeId]) ? $fields['ORDER_PROP_'.$options['PropStreet_'.$pTypeId]] : false;
        $home = ($options['PropHome_'.$pTypeId]) ? $fields['ORDER_PROP_'.$options['PropHome_'.$pTypeId]] : false;
        $corps = ($options['PropCorp_'.$pTypeId]) ? $fields['ORDER_PROP_'.$options['PropCorp_'.$pTypeId]] : false;
        $ap = ($options['PropApartament_'.$pTypeId]) ? $fields['ORDER_PROP_'.$options['PropApartament_'.$pTypeId]] : false;

		//$address = ($addressId > 0) ? $fields['ORDER_PROP_'.$addressId] : false;
        $address = ($options['PropAddress_'.$pTypeId]) ? $fields['ORDER_PROP_'.$options['PropAddress_'.$pTypeId]] : false;

        //местополложение
		//print_r($options['PropCity_'.$pTypeId]);
		//var_dump($location);
		//print_r($formProps);
        
		if ($city) {
            $status = "Y";
        }
        elseif ($options['PropCity_'.$pTypeId] > 0 && !$location) {
            $status = "Y";
            if (strlen($fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]]) > 1) {
                $city = $fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]];
            }
            elseif(strlen($formProps['PropCity']) > 1){
				$city = $formProps['PropCity'];
			}
            else {
                $error[] = GetMessage('RequireCity');
                TwinpxApi::SetLogs($city, '', 'getRegion.location'); //
            }
            $emptyFields['PropCity'] = "ORDER_PROP_".$options['PropCity_'.$pTypeId];
        }
        else {
            $status = "Y";
            $error[] = GetMessage('RequireCity');
            TwinpxApi::SetLogs($city, '', 'getRegion.location'); //
        }

        //фио
        if (strlen($fio) < 1) {
            if (strlen($formProps['PropFio']) < 1) {
                $error[] = GetMessage('Require');
            }
            else {
                $fio = $formProps['PropFio'];
            }
        }

        //email
        if (strlen($email) < 1) {
            if (strlen($formProps['PropEmail']) < 1) {
                //не обязательное поле, не выводим ошибку
            }
            else {
                $email = $formProps['PropEmail'];
            }
        }

        //телефон
        $phone = preg_replace('![^0-9+]+!', '', $phone); //чистим от лишних символов
        if (strlen($phone) < 1) {
            $phone = preg_replace('![^0-9+]+!', '', $formProps['PropPhone']); //чистим от лишних символов
            if (strlen($phone) < 1) {
                $error[] = GetMessage('Require');
            }
            elseif (strlen($phone) > 12) {
                $error[] = GetMessage('LengthPhone');
            }
            else {
                $phone = $phone;
            }
        }
        elseif (strlen($phone) > 12) {
            $error[] = GetMessage('LengthPhone');
        }

        $emptyFields['PropFio'] = "ORDER_PROP_".$options['PropFio_'.$pTypeId];
        $emptyFields['PropPhone'] = "ORDER_PROP_".$options['PropPhone_'.$pTypeId];
        $emptyFields['PropEmail'] = "ORDER_PROP_".$options['PropEmail_'.$pTypeId];

		//адрес
        if ($options['PropAddress_'.$pTypeId] > 0) //не задан адрес
        {
        	if (strlen($address) < 1) {
	        	if (strlen($formProps['PropAddress']) < 1) {
	            	$error[] = GetMessage('Require');
	            }
	            else {
	            	$address = $formProps['PropAddress'];
	            }
			}
            $emptyFields['PropAddress'] = "ORDER_PROP_".$options['PropAddress_'.$pTypeId];
        }
        elseif ( $options['PropStreet_'.$pTypeId] > 0 && $options['PropHome_'.$pTypeId] > 0 && $options['PropApartament_'.$pTypeId] > 0 ) //если есть привязки для "Улицы", "Дом", "Кв."
        {
              if (strlen($street) < 1) {
                  if (strlen($formProps['PropStreet']) < 1) {
                      $error[] = GetMessage('Require');
                  }
                  else {
                      $street = $formProps['PropStreet'];
                  }
              }
              if (strlen($home) < 1) {
                  if (strlen($formProps['PropHome']) < 1) {
                      $error[] = GetMessage('Require');
                  }
                  else {
                      $home = $formProps['PropHome'];
                  }
              }
              if (strlen($corps) < 1) {
                  if (strlen($formProps['PropCorp']) < 1) {
                      //$error[] = GetMessage('Require');
                  }
                  else {
                      $corps = $formProps['PropCorp'];
                  }
              }
              if (strlen($ap) < 1) {
                  if (strlen($formProps['PropAp']) < 1) {
                      $error[] = GetMessage('Require');
                  }
                  else {
                      $ap = $formProps['PropAp'];
                  }
              }

              $emptyFields['PropStreet'] = "ORDER_PROP_".$options['PropStreet_'.$pTypeId];
              $emptyFields['PropHome'] = "ORDER_PROP_".$options['PropHome_'.$pTypeId];
              $emptyFields['PropCorp'] = "ORDER_PROP_".$options['PropCorp_'.$pTypeId];
              $emptyFields['PropAp'] = "ORDER_PROP_".$options['PropApartament_'.$pTypeId];
          }
		else //нет привязки
		{
		  if (strlen($address) < 1) {
		      if (strlen($formProps['PropAddress']) < 1) {
		          $error[] = GetMessage('Require');
		      }
		      else {
		          $address = $formProps['PropAddress'];
		      }
		  }
		  $emptyFields['PropAddress'] = "ORDER_PROP_".$options['PropAddress_'.$pTypeId];
		}

        //комменатрий
        if ($options['PropComment_'.$pTypeId] == 'ORDER_DESCRIPTION') //если нет привязки комментарий не будет передаватся
        {
            $comment = $fields["ORDER_DESCRIPTION"];
            $emptyFields['PropComment'] = "ORDER_DESCRIPTION";
        }
        elseif ( $options['PropComment_'.$pTypeId] > 0 ) {
            $comment = $fields["ORDER_PROP_".$options['PropComment_'.$pTypeId]];
            $emptyFields['PropComment'] = "ORDER_PROP_".$options['PropComment_'.$pTypeId];
        }
        else {
            $comment = $formProps['PropComment'];
        }
		
		$session->set('YD_DELIVERY_ID', $deliveryID);
		
        $cost = ($session->has('YD_CURIER_PRICE')) ? floatval($session->get('YD_CURIER_PRICE')) : FALSE;
        
        //если известен ID получаем настройки доставки
        if ($deliveryID) {
            $session->set('YD_DELIVERY_ID', $deliveryID);
            $rsProfile = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $deliveryID), 'select' => array('ID', 'PARENT_ID', 'CONFIG')));
            if ($profile = $rsProfile->fetch()) {
                $profileMargin = array('MARGIN_VALUE'=> $profile['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $profile['CONFIG']['MAIN']['MARGIN_TYPE']);
                $profileSettings = unserialize(unserialize($profile['CONFIG']['MAIN']['OLD_SETTINGS'])); //настройки профиля
                $rsDelivery = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $profile['PARENT_ID']),'select' => array('ID', 'PARENT_ID', 'CONFIG')));
                if ($delivery = $rsDelivery->fetch()) {
                    $deliveryMargin = array('MARGIN_VALUE'=> $delivery['CONFIG']['MAIN']['MARGIN_VALUE'], 'MARGIN_TYPE' => $delivery['CONFIG']['MAIN']['MARGIN_TYPE']);
                }
            }
		}
		
        $calculateError = FALSE;
        //если нет фик. считаем динамично
        if ($cost === FALSE && strlen($city) > 1){
            $arData = array(
                'CITY'          => $city,
                'PAYMENT_METHOD'=> $payment,
                'TARIFF'        => 'time_interval',
                'INSURANCE' 	=> $profileSettings['CURIER_INSURANCE']
            );
            $prepareCalculator = TwinpxApi::PrepareDataCalculate($arData);
            $price             = TwinpxApi::requestPost('/api/b2b/platform/pricing-calculator', $prepareCalculator);
            if (strlen($price['DATA']['pricing_total']) > 1 && $price['SUCCESS']) {
                $priceValue = explode(" ", $price['DATA']['pricing_total']);
                $cost       = ($options['Round'] == 'Y' && false) ? ceil($priceValue[0]) : floatval($priceValue[0]);
                $session->set("INIT_PRICE", $cost);
            }
            else {
                $calculateError = TRUE;
                TwinpxApi::SetLogs($price, '', 'getOffer.calculate'); //
            }
        }
        
        if (!empty($error)) //если есть ошибки
        {
            $status = "Y";
            TwinpxApi::SetLogs($output, 'ERROR', 'getOffer.errors'); //
            $error  = array_unique($error);
            $errors = implode('<br/>', $error);
            //output
            $output = array("STATUS" => $status, "FIELDS"=> $emptyFields, "ERRORS" => $errors);
        }
        elseif ($calculateError) //при ошибки расчета
        {
            $status = "Y";
            $errors = GetMessage('No-intervals');
            $output = array("STATUS" => $status,"OFFERS" => array(),"ERRORS" => $errors);
        }
        else //когда нет ошибок
        {
            //считаем наценку если она заданао
            if ($deliveryID) {
                //$session->set('YD_DELIVERY_ID', $deliveryID);
                /*$rsProfile = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $deliveryID), 'select' => array('ID', 'PARENT_ID', 'CONFIG')));
                if ($profile = $rsProfile->fetch()) {
                    $profileMargin = array('MARGIN_VALUE'=> $profile['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $profile['CONFIG']['MAIN']['MARGIN_TYPE']);
                    $profileSettings = unserialize(unserialize($profile['CONFIG']['MAIN']['OLD_SETTINGS'])); //настройки профиля
                    $rsDelivery = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $profile['PARENT_ID']),'select' => array('ID', 'PARENT_ID', 'CONFIG')));
                    if ($delivery = $rsDelivery->fetch()) {
                        $deliveryMargin = array('MARGIN_VALUE'=> $delivery['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $delivery['CONFIG']['MAIN']['MARGIN_TYPE']);
                    }
                }*/

                //наценка доставки
                if ($deliveryMargin['MARGIN_TYPE'] == 'CURRENCY') {
                    $cost = $cost + floatval($deliveryMargin['MARGIN_VALUE']);
                }
                else {
                    $cost = $cost + ( $cost * (floatval($deliveryMargin['MARGIN_VALUE']) / 100 ));
                }

                //наценка профиля
                if ($profileMargin['MARGIN_TYPE'] == 'CURRENCY') {
                    $cost = $cost + floatval($profileMargin['MARGIN_VALUE']);
                }
                else {
                    $cost = $cost + ( $cost * (floatval($profileMargin['MARGIN_VALUE']) / 100 ));
                }
				
				//получаем скидку на доставку если есть
                $cost = ($cost >= 0 ) ? round($cost, 2, PHP_ROUND_HALF_UP) : 0; //округляем до сотых
                $cost = ($options['Round'] == 'Y') ? round($cost) : $cost;
                $session->set('MARGIN_VALUE', $cost); //сохраняем цена с наценки
            }
			$cost = ($options['Round'] == 'Y') ? round($cost) : floatval($cost); //цена доставки для курьера
			
            //массив с данные
            $arFields = array(
                //'FIO'         => explode(" ", $fio),
                'FIO'         => $fio,
                'PHONE'       => $phone,
                'EMAIL'       => $email,

                'CITY'        => $city,
                'STREET'      => $street,
                'HOME'        => $home,
                'CORPS'       => $corps,
                'APARTAMENT'  => $ap,

                'FULL_ADDRESS'=> $address,

                'COMMENT'     => $comment,
                'PAYMENT'     => $payment,

                'FIX_PRICE'   => $cost, 
                'INSURANCE'   => $profileSettings['CURIER_INSURANCE']
            );
			
			$session->set("CALCULATE_PRICE", $cost);

            $full_address     = ($addressId > 0) ? $city.', '.$address : TwinpxApi::PrepareAddress($arFields);
			
            $getInterval      = TwinpxApi::GetInterval($full_address); //получаем доступные интервалы
            $prepare          = TwinpxApi::PrepareData($arFields);
						
			$offer = array("SUCCESS" => false);
			if(!empty($getInterval)){
            	$offer = TwinpxApi::multiRequest('/api/b2b/platform/offers/create', $prepare, $getInterval);
			}
            
            if ($offer['SUCCESS'] AND !empty($offer['DATA'])) //если получили офферы
            {
                $status = "Y";
                $result = TwinpxApi::ShowOfferJson($offer['DATA']);
                //output
                $output = array("STATUS" => $status, "OFFERS" => $result, "ERRORS"=> $errors);
            }
            elseif ($offer['SUCCESS'] AND !empty($offer['ERROR'])) //обработка ошибок
            {
                $adr = FALSE;
                foreach ($offer['ERROR'] as $value) {
                    if ( is_array($value) && in_array ( "cannot parse destination info" , $value ) ) {
                        $adr = TRUE;
                    }
                }
                //если нашли ошибку адреса
                if ($adr) {
                    TwinpxApi::SetLogs(json_encode($offer), '', 'getOffer.addressError'); //
                    $status = "Y";
                    $errors = GetMessage('Wrong-Address');
                    //output
                    $output = array("STATUS"=> $status, "FIELDS"=> $emptyFields, "ERRORS"=> $errors);
                }
                else {
                    TwinpxApi::SetLogs(json_encode($offer), '', 'getOffer.noOffer'); //
                    $status = "Y";
                    $errors = GetMessage('No-intervals');
                    $output = array("STATUS"=> $status, "OFFERS" => array(), "ERRORS"=> $errors);
                }
            }
            else //нет офферов
            {
                TwinpxApi::SetLogs(json_encode($offer), '', 'getOffer.noOffer'); //
                $status = "Y";
                $errors = GetMessage('No-intervals');
                $output = array("STATUS"=> $status, "OFFERS" => array(), "ERRORS"=> $errors);
            }
        }

        echo \Bitrix\Main\Web\Json::encode($output);
    }

    //записываем цену в доставки
    if ($request["action"] == 'price') {
        $price       = floatval($request['price']);
        $offerID     = $request['offer'];
        $offerExpire = $request['expire'];

        $session->set('YD_SETPRICE', $price);
        $session->set('YD_OFFER_ID', $offerID);
        $session->set('YD_OFFER_EXPIRE', $offerExpire);
    }

    //возврат адреса региона из битрикс
    if ($request["action"] == 'getRegion') {
        parse_str($request["fields"], $fields);
        if (LANG_CHARSET != 'UTF-8') {
            $fields = \Bitrix\Main\Text\Encoding::convertEncoding($fields, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
        }
        TwinpxApi::SetLogs($fields, '', 'getRegion.fields'); //

        $options = TwinpxConfigTable::GetAllOptions();
        $pTypeId = $fields['PERSON_TYPE']; //тип плательщика
        if ($pTypeId > 0) {
            $session->set('PERSON_TYPE', $pTypeId);
        }
        $status       = "N";
        $full_address = '';
        $error        = array();

        //$location = ($options['PropCity_'.$pTypeId] > 0) ? CSaleLocation::GetByID($fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]], LANGUAGE_ID) : CSaleLocation::GetByID("0000073738", LANGUAGE_ID); //если не указан регион передаем москва
        $location = ($options['PropCity_'.$pTypeId] > 0) ? TwinpxDelivery::GetLocationByCode( $fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]] ) : FALSE; //если не указан регион передаем москва

        if ($location) {
            $status = "Y";
            $full_address = $location;
        }
        elseif ($options['PropCity_'.$pTypeId] > 0 && $location === false) {
            $status = "Y";
            if (strlen($fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]]) > 1) {
                $full_address = $fields['ORDER_PROP_'.$options['PropCity_'.$pTypeId]];
            }
            else {
                $error[] = GetMessage('RequireCity');
                TwinpxApi::SetLogs($location, '', 'getRegion.location'); //
            }
        }
        else {
            $status = "Y";
            $error[] = GetMessage('RequireCity');
            TwinpxApi::SetLogs($location, '', 'getRegion.location'); //
        }

        $thisPayID = intval($fields['PAY_SYSTEM_ID']); //
        $payment   = false;
        if ($thisPayID > 0) {
            //если есть тип платежа
            if (strlen($options['Pay_'.$thisPayID]) > 0) {
                $payment = $options['Pay_'.$thisPayID];
            }
            else {
                $status = "Y";
                $error[] = GetMessage('PaymentError');
                TwinpxApi::SetLogs(array(GetMessage('PaymentError')), '', 'getRegion.payment');
            }
        }

        echo \Bitrix\Main\Web\Json::encode(array("STATUS" => $status, "REGION" => $full_address, "PAYMENT"=> $payment, "ERRORS" => implode("<br/>", $error)));
    }

    //получение список ПВЗ для региона
    if ($request["action"] == 'getPoints') {
        parse_str($request["fields"], $fields);
        if (LANG_CHARSET != 'UTF-8') {
            $fields = \Bitrix\Main\Text\Encoding::convertEncoding($fields, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
        }
        TwinpxApi::SetLogs(json_encode($fields), '', 'getPoints.fields');

        $status = "N";
        $errors = false;
        $points = false;
        $error = array();
        $allPoints = array();
        $type = array(
            "pickup_point"  => GetMessage('Pickup-point'),
            "terminal"      => GetMessage('Terminal'),
            "post_office"   => GetMessage('Post-office'),
            "sorting_center"=> GetMessage('Sorting-center'),
            "warehouse"     => GetMessage('Warehouse'),
        );

        if ($fields['payment'] != 'false') {
            $queryPvz = array(
                "available_for_dropoff"=> FALSE,
                "payment_method"       => $fields['payment'],
                "latitude"             => ["from" => floatval($fields['lat-from']),"to" => floatval($fields['lat-to'])],
                "longitude"            => ["from" => floatval($fields['lon-from']),"to" => floatval($fields['lon-to'])],
            );
            $response = TwinpxApi::requestPost("/api/b2b/platform/pickup-points/list", $queryPvz);
        }
        else {
            $error[] = GetMessage('PaymentError');
            TwinpxApi::SetLogs(array(GetMessage('PaymentError')), '', 'getPoints.payment');
        }

        //обработка результата
        if ($response['SUCCESS'] && !empty($response['DATA']['error'])) {
            $status = "Y";
            //$error[] = $response['DATA']['error']['error_details'] ? $response['DATA']['error']['error_details'] : $response['DATA']['error'];
            $error[] = GetMessage('pvz_empty');
            TwinpxApi::SetLogs(json_encode($response['DATA']['error']), '', 'getPoints.error');
        }
        elseif ($response['SUCCESS'] && !isset($response['DATA']['message'])) // [message] => object_not_found
        {
            $status = "Y";
            if(empty($response['DATA'])){ //если пустой список
				$error[] = GetMessage('pvz_empty');
				TwinpxApi::SetLogs(json_encode($response['DATA']), '', 'getPoints.error'); //
			}
			else {
	            foreach ($response['DATA'] as $point) {
	            	//исключение
	                if ($point['type'] == 'sorting_center' 
	                	|| $point['type'] == 'post_office'
	                	|| $point['is_post_office'] == 'true' )
					{
						continue;
					}
	                //if ($point['type'] == 'sorting_center') continue;
	                //
	                $data = array(
	                    'id'     => $point['id'],
	                    'title'  => $point['name'],
	                    'type'   => ($type[$point['type']] != NULL) ? $type[$point['type']] : '',
	                    'address'=> ($point['address']['full_address']) ? $point['address']['full_address'] : $point['address']['locality'].', '.$point['address']['street'].', '.$point['address']['house'],
	                );
	                $jsonData = \Bitrix\Main\Web\Json::encode($data);//передаем данные
	                $hash     = md5($point['name'].$point['type'].$point['position']['latitude'].$point['position']['longitude']); //хэш значение для исключение одинаковых точек
	                $allPoints[$hash] = array(
	                    "id"      => $point['id'],
	                    "title"   => $point['name'],
	                    "type"    => ($type[$point['type']] != NULL) ? $type[$point['type']] : '',
	                    "schedule"=> TwinpxApi::GenerateSchedule($point['schedule']['restrictions']),
	                    "address" => ($point['address']['full_address']) ? $point['address']['full_address'] : $point['address']['locality'].', '.$point['address']['street'].', '.$point['address']['house'],
	                    "coords"   => array($point['position']['latitude'],$point['position']['longitude']),
	                    "json"    => $jsonData
	                );
	            }
	        }
        }
        else {
            $status = "Y";
            $error[] = GetMessage($response['DATA']['message']);
            TwinpxApi::SetLogs(json_encode($response['DATA']['message']), '', 'getPoints.error');
        }

        if (!empty($allPoints)) $points = array_values($allPoints);
        if (!empty($error)) $errors = implode('<br/>', $error);


        echo \Bitrix\Main\Web\Json::encode(array("STATUS"=> $status, "POINTS"=> $points, "ERRORS" => $errors));
    }

    //получение список офферов для ПВЗ
    if ($request["action"] == 'pvzOffer') {
        parse_str($request['props'], $formProps);
        $parse = explode('&', $request["fields"]);
        $fields= array();
        foreach ( $parse as $param ) {
            if (strpos($param, '=') === false) $param += '=';
            list($name, $value) = explode('=', $param, 2);
            $fields[urldecode($name)][] = urldecode($value);
        }
        if (LANG_CHARSET != 'UTF-8') {
            $fields    = \Bitrix\Main\Text\Encoding::convertEncoding($fields, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
            $formProps = \Bitrix\Main\Text\Encoding::convertEncoding($formProps, "UTF-8", LANG_CHARSET); //для кодировки windows - 1251
        }
        TwinpxApi::SetLogs($fields, '', 'pvzOffer.fields');
        //перебор значение
        if (!empty($fields)) {
            foreach ($fields as $name => $value) {
                if (count($value) > 1) {
                    $arFields[$name] = '';
                    foreach ($value as $v) {
                        if ($v != '') $arFields[$name] = $v; //берем значение которые заполнены только
                    }
                }
                else {
                    $arFields[$name] = $value[0];
                }
            }
        }
        $fields = $arFields;
        unset($arFields);

        $options   = TwinpxConfigTable::GetAllOptions();
        $pTypeId   = $fields['PERSON_TYPE']; //тип плательщика
        $thisPayID = $fields['PAY_SYSTEM_ID']; //
        $deliveryID= $fields['DELIVERY_ID']; //id доставки
        $pvzId     = $fields['id'];
        $name      = $fields['title'];
        $pvzAddress= $fields['address'];
        $status    = "N";
        $points    = array();
        $error = $log   = array();
        $emptyFields = array();

        $session->set('PVZ_FULLADDRESS', $name .', '. $pvzAddress);

        //получаем свойствы заказа
        //личные данные
        $fio = ($options['PropFio_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropFio_'.$pTypeId]] : false;
        $phone = ($options['PropPhone_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropPhone_'.$pTypeId]] : false;
        $email = ($options['PropEmail_'.$pTypeId]) ? $fields["ORDER_PROP_".$options['PropEmail_'.$pTypeId]] : "";

        $emptyFields['PropFio'] = "ORDER_PROP_".$options['PropFio_'.$pTypeId];
        $emptyFields['PropPhone'] = "ORDER_PROP_".$options['PropPhone_'.$pTypeId];
        $emptyFields['PropEmail'] = "ORDER_PROP_".$options['PropEmail_'.$pTypeId];
//        $emptyFields['PropAddress'] = ($addressId > 0 ) ? "ORDER_PROP_".$addressId : '';
        $emptyFields['PropAddress'] = "ORDER_PROP_".$options['PropAddress_'.$pTypeId];

        //получаем информацию по платежной системе
        if ($thisPayID > 0) {
            //если есть тип платежа
            if (strlen($options['Pay_'.$thisPayID]) > 0) {
                $payment = $options['Pay_'.$thisPayID];
            }
            else {
                $error[] = GetMessage('PaymentError');
            }
            $session->set('YD_PAY_ID', $thisPayID);
        }

        //фио
        if (strlen($fio) < 1) {
            if (strlen($formProps['PropFio']) < 1) {
                $error[] = GetMessage('Require');
            }
            else {
                $fio = $formProps['PropFio'];
            }
        }

        //email
        if (strlen($email) < 1) {
            if (strlen($formProps['PropEmail']) < 1) {
                //не обязательное поле, не выводим ошибку
            }
            else {
                $email = $formProps['PropEmail'];
            }
        }

        //телефон
        $phone = preg_replace('![^0-9+]+!', '', $phone); //чистим от лишних символов
        if (strlen($phone) < 1) {
            $phone = preg_replace('![^0-9+]+!', '', $formProps['PropPhone']); //чистим от лишних символов
            if (strlen($phone) < 1) {
                $error[] = GetMessage('Require');
            }
            elseif (strlen($phone) > 12) {
                $error[] = GetMessage('LengthPhone');
            }
            else {
                $phone = $phone;
            }
        }
        elseif (strlen($phone) > 12) {
            $error[] = GetMessage('LengthPhone');
        }
        
        if ($deliveryID) {
            $session->set('YD_DELIVERY_ID', $deliveryID);
            $rsProfile = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $deliveryID),'select' => array('ID','PARENT_ID','CONFIG')));
            if ($profile = $rsProfile->fetch()) {
                $profileMargin = array('MARGIN_VALUE'=> $profile['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $profile['CONFIG']['MAIN']['MARGIN_TYPE']);
                $profileSettings = unserialize(unserialize($profile['CONFIG']['MAIN']['OLD_SETTINGS'])); //настройки профиля
                $rsDelivery = Table::getList(array('filter' => array('ACTIVE'=> 'Y','ID'    => $profile['PARENT_ID']),'select' => array('ID','PARENT_ID','CONFIG')));
                if ($delivery = $rsDelivery->fetch()) {
                    $deliveryMargin = array('MARGIN_VALUE'=> $delivery['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $delivery['CONFIG']['MAIN']['MARGIN_TYPE']);
                }
            }
		}
				
        $cost = ($session->has('YD_PICKUP_PRICE')) ? intval($session->get('YD_PICKUP_PRICE')) : FALSE;
        $calculateError = FALSE;
        //если нет фик. считаем динамично
        if ($cost === FALSE && strlen($pvzAddress) > 1) {
            $arData = array(
                'CITY'          => $pvzAddress,
                'PAYMENT_METHOD'=> $payment,
                'TARIFF'        => 'self_pickup',
                //'INSURANCE' 	=> "N" //отключаем страховку для ПВЗ, для почта Россий обязательно должна быть.
                'INSURANCE' 	=> $profileSettings['PICKUP_INSURANCE']
            );
            $prepareCalculator = TwinpxApi::PrepareDataCalculate($arData);
            $price             = TwinpxApi::requestPost('/api/b2b/platform/pricing-calculator', $prepareCalculator);
            if (strlen($price['DATA']['pricing_total']) > 1 && $price['SUCCESS']) {
                $priceValue = explode(" ", $price['DATA']['pricing_total']);
                $cost       = ($options['Round'] == 'Y' && false) ? ceil($priceValue[0]) : floatval($priceValue[0]);
                $session->set("INIT_PRICE", $cost);
            }
            else {
                $calculateError = TRUE;
                TwinpxApi::SetLogs($price, '', 'pvzOffer.calculate'); //
            }
        }
        
        if (!empty($error)) {
            TwinpxApi::SetLogs($error, '', 'pvzOffer.error');
            $status = "Y";
            $error  = array_unique($error);
            $errors = implode('<br/>', $error);
            //output
            $output = array("STATUS"=> $status,"FIELDS"=> $emptyFields,"ERRORS"=> $errors);
        }
        elseif ($calculateError) {
            $status = "Y";
            $errors = GetMessage('No-intervals');
            $output = array("STATUS"=> $status,"OFFERS" => array(),"ERRORS"=> $errors);
        }
        else {
            //считаем наценку если она заданао
            if ($deliveryID) {
                /*$session->set('YD_DELIVERY_ID', $deliveryID);
                $rsProfile = Table::getList(array('filter' => array('ACTIVE'=> 'Y', 'ID' => $deliveryID),'select' => array('ID','PARENT_ID','CONFIG')));
                if ($profile = $rsProfile->fetch()) {
                    $profileMargin = array('MARGIN_VALUE'=> $profile['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $profile['CONFIG']['MAIN']['MARGIN_TYPE']);
                    $rsDelivery = Table::getList(array('filter' => array('ACTIVE'=> 'Y','ID'    => $profile['PARENT_ID']),'select' => array('ID','PARENT_ID','CONFIG')));
                    if ($delivery = $rsDelivery->fetch()) {
                        $deliveryMargin = array('MARGIN_VALUE'=> $delivery['CONFIG']['MAIN']['MARGIN_VALUE'],'MARGIN_TYPE' => $delivery['CONFIG']['MAIN']['MARGIN_TYPE']);
                    }
                }*/

                //наценка доставки
                if ($deliveryMargin['MARGIN_TYPE'] == 'CURRENCY') {
                    $cost = $cost + floatval($deliveryMargin['MARGIN_VALUE']);
                }
                else {
                    $cost = $cost + ( $cost * (floatval($deliveryMargin['MARGIN_VALUE']) / 100 ));
                }

                //наценка профиля
                if ($profileMargin['MARGIN_TYPE'] == 'CURRENCY') {
                    $cost = $cost + floatval($profileMargin['MARGIN_VALUE']);
                }
                else {
                    $cost = $cost + ( $cost * (floatval($profileMargin['MARGIN_VALUE']) / 100 ));
                }

                //получаем скидку на доставку если есть
                $cost = ($cost >= 0 ) ? round($cost, 2, PHP_ROUND_HALF_UP) : 0; //округляем до сотых
                $cost = ($options['Round'] == 'Y') ? round($cost) : $cost;
                $session->set('MARGIN_VALUE', $cost); //сохраняем наценку
            }
            $cost = ($options['Round'] == 'Y') ? round($cost) : floatval($cost); //цена доставки для курьера

            $arFields = array(
                //'FIO'         => explode(" ", $fio),
                'FIO'         => $fio,
                'PHONE'       => $phone,
                'EMAIL'       => $email,

                'COMMENT'     => "",

                'PVZ_ID'      => $pvzId,
                'FULL_ADDRESS'=> $pvzAddress,

                'PAYMENT'     => $payment,

                'FIX_PRICE'   => $cost,
                //'INSURANCE'   => "N"
                'INSURANCE'   => $profileSettings['PICKUP_INSURANCE']
            );
			
			$session->set("CALCULATE_PRICE", $cost);

            $prepare = TwinpxApi::PrepareData($arFields);
            $offer   = TwinpxApi::requestPost('/api/b2b/platform/offers/create', $prepare); //запрос
            
            if ($offer['SUCCESS'] && !empty($offer['DATA']) && empty($offer['DATA']['error'])) {
                $status = "Y";
                $result = TwinpxApi::ShowOfferJson($offer['DATA'][0], $pvzId);
                //output
                $output = array("STATUS"=> $status, "OFFERS"=> $result, "ERRORS"=> $errors);
                TwinpxApi::SetLogs(json_encode($offer), '', 'pvzOffer.offers'); //
            }
            elseif ($offer['SUCCESS'] AND !empty($offer['ERROR'])) {
                $adr = FALSE;
                foreach ($offer['ERROR'] as $value) {
                    if ( in_array ( "incorrect delivery address or house number not stated, please check" , $value ) ) {
                        $adr = TRUE;
                    }
                }
                //если нашли ошибку адреса
                if ($adr) {
                    $status = "Y";
                    $errors = GetMessage('Wrong-Address');
                    //output
                    $output = array("STATUS"=> $status,"OFFERS" => array(),"ERRORS"=> $errors);
                    TwinpxApi::SetLogs(json_encode($offer), '', 'pvzOffer.address'); //
                }
                else {
                    $status = "Y";
                    $errors = GetMessage('No-intervals');
                    //output
                    $output = array("STATUS"=> $status,"OFFERS" => array(),"ERRORS"=> $errors);
                    TwinpxApi::SetLogs(json_encode($offer), '', 'pvzOffer.noOffer'); //
                }
            }
            else {
                $status = "Y";
                $errors = GetMessage('No-intervals');
                //output
                $output = array("STATUS"=> $status,"OFFERS" => array(), "ERRORS"=> $errors);
                TwinpxApi::SetLogs(json_encode($offer), '', 'pvzOffer.error'); //
            }
        }

        echo \Bitrix\Main\Web\Json::encode($output);
    }

    //передаем цену для доставки
    if ($request["action"] == 'setOfferPrice') {
        $fields = \Bitrix\Main\Web\Json::decode($request["fields"]); //парсим JSON
        $status = "Y";
        
        $basketItems 	= Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        $basket 		= $basketItems->getOrderableItems();
		//$basket      	= $basketItems->getBasket();
        $basketSumm 	= $basket->getBasePrice(); //сумма без скидок
        
        $price  = floatval($fields['offer_price']);
        $cost   = floatval($fields['cost']);
        $offerID= ($fields['offer_id']) ? $fields['offer_id'] : FALSE;
        $pvzID = ($fields['offer_pvz']) ? $fields['offer_pvz'] : FALSE;
        $offerExpire = ($fields['offer_expire']) ? $fields['offer_expire'] : FALSE;

        $session->set('YD_OFFER_PRICE', $price); //сохраняем цену оффера

        $price       = ($session->has('CALCULATE_PRICE')) ? $session->get('CALCULATE_PRICE') : $price;
		$initPrice = ($session->has('INIT_PRICE')) ? $session->get('INIT_PRICE') : $price; //цена без наценки

        $emptyFields = array();
        if ($session->has('PERSON_TYPE') && $session->get('PERSON_TYPE') > 0) {
            $options = TwinpxConfigTable::GetAllOptions(); //настройки
            $pTypeId = $session->get('PERSON_TYPE');
            /*$dbProps = CSaleOrderProps::GetList(array("SORT"=> "ASC"), array("IS_ADDRESS"    => "Y","ACTIVE"        => "Y","PERSON_TYPE_ID"=> $pTypeId), false, false, array());
            if ($prop = $dbProps->Fetch()) {
                $addressId = $prop['ID'];
            }*/
            
            $emptyFields['PropFio'] = ($options['PropFio_'.$pTypeId] > 0) ? "ORDER_PROP_".$options['PropFio_'.$pTypeId] : NULL;
            $emptyFields['PropPhone'] = ($options['PropPhone_'.$pTypeId] > 0) ? "ORDER_PROP_".$options['PropPhone_'.$pTypeId] : NULL;
            $emptyFields['PropEmail'] = ($options['PropEmail_'.$pTypeId] > 0) ? "ORDER_PROP_".$options['PropEmail_'.$pTypeId] : NULL;
            $emptyFields['PropAddress'] = ($options['PropAddress_'.$pTypeId] > 0) ? "ORDER_PROP_".$options['PropAddress_'.$pTypeId] : NULL;
        }

        //задаем цену в зависимомть от типа
        if ($pvzID) {
            $session->set('YD_PVZPRICE', $initPrice);
        }
        else {
            $session->set('YD_CURIERPRICE', $initPrice);
        }
		
		$session->set('YD_BASKET_SUMM', $basketSumm);
        $session->set('YD_PVZ_ID', $pvzID);
        $session->set('YD_OFFER_ID', $offerID);
        $session->set('YD_SETPRICE', $offerExpire);

        $session->remove('PERSON_TYPE');

        echo \Bitrix\Main\Web\Json::encode(array("STATUS"=> $status,"FIELDS"=> $emptyFields));
    }

    //записываем оффер
    if ($request["action"] == 'setDelivery') {
        $params           = \Bitrix\Main\Web\Json::decode($request->get("data"));
        $order_id         = $params['order_id'];
        $offer_id         = $params['offer_id'];
        $location         = ($session->has('LOCATION_CODE')) ? $session->get('LOCATION_CODE') : '_';
        $full_address     = ($session->has('YD_FULL_ADDRESS')) ? $session->get('YD_FULL_ADDRESS') : '';

        $deliveryInterval = '';
        foreach ($session->get('JSON_ANSWER') as $json_answer) {
            foreach ($json_answer as $answer) {
                if ($answer['offer_id'] == $offer_id) {
                    $start            = TwinpxApi::PrepareDataTime($answer['offer_details']['delivery_interval']['min']);
                    $end              = TwinpxApi::PrepareDataTime($answer['offer_details']['delivery_interval']['max']);
                    $deliveryInterval = $start . ' - ' . $end;
                }
            }
        }

        //приготовим данные для записи в таблицу
        $data = array(
            'ORDER_ID'         => $order_id,
            'ORDER_DATE'       => new \Bitrix\Main\Type\DateTime(),
            'OFFER_ID'         => $offer_id,
            'ADDRESS'          => $full_address,
            'LOCATION'         => $location,
            'JSON_REQUEST'     => ($session->has('JSON_REQUEST')) ? serialize($session->get('JSON_REQUEST')) : null,
            'JSON_RESPONS'     => ($session->has('JSON_ANSWER')) ? serialize($session->get('JSON_ANSWER')) : null,
            'DELIVERY_INTERVAL'=> $deliveryInterval,
            'BARCODE'          => $session->get('YD_BARCODE')
        );
        $r            = TwinpxOfferTable::add($data); //создаем записи


        $offerRequest = array("offer_id"=> $offer_id);
        $create = TwinpxApi::requestPost('/api/b2b/platform/offers/confirm', $offerRequest); //бронируем оффер

        //если получили ответ
        if ($create['SUCCESS'] AND $create['DATA']['request_id']) {
            $requestID = $create['DATA']['request_id'];
            $data      = array('REQUEST_ID'=> $requestID);
            TwinpxOfferTable::update($r->GetID(), $data);

            //записи статуса доставки
            $state = TwinpxApi::GetOfferState($requestID);
            if ($state['STATUS']) {
                $data = array(
                    'STATUS'            => $state['STATUS'],
                    'STATUS_DESCRIPTION'=> $state['DESCRIPTION']
                );
                TwinpxOfferTable::update($r->GetID(), $data);
            }

            //получаем ID и название наще доставки
            $rsDelivery = \Bitrix\Sale\Delivery\Services\Table::getList(array('filter' => array('ACTIVE'=>'Y','=CODE' => 'twpx_yadelivery'),'select' => array('ID','NAME')));
            if ($delivery = $rsDelivery->fetch()) {
                $rsProfile = \Bitrix\Sale\Delivery\Services\Table::getList(array(
                        'filter' => array('ACTIVE'   => 'Y','PARENT_ID'=> $delivery['ID'],'=CODE'    => 'twpx_yadelivery:pickup'),
                        'select' => array('*')
                    ));
                $profile = $rsProfile->fetch();//информация о доставке
            }

            //если нашли достаку
            if ($delivery) {
                //получаем корзину
                $order              = \Bitrix\Sale\Order::load($order_id);
                $shipmentCollection = $order->getShipmentCollection();
                foreach ($shipmentCollection as $shipment) {
                    if ($shipment->isSystem())
                    continue;
                    $shipment->setFields(array(
                            'DELIVERY_ID'  => $profile['ID'],
                            'DELIVERY_NAME'=> $delivery['NAME'].' ('.$profile['NAME'].')'
                        ));
                    $shipment->allowDelivery();
                }
                $res = $order->save();
                if (!$res->isSuccess()) {
                    TwinpxApi::SetLogs($res->getErrors(), '', 'setDelivery.ordersave');
                }
            }

            $result = array('SUCCESS'=> 'Y'); //успешно
        }
        else {
            $data = array(
                'STATUS'            => 'CREATED_ERROR',
                'STATUS_DESCRIPTION'=> GetMessage("TWINPX_YADELIVERY_OSIBKA_SOZDANIA_ZAAV")
            );
            TwinpxOfferTable::update($r->GetID(), $data);

            $result = array('SUCCESS'=> 'N','ERROR'  => GetMessage("TWINPX_YADELIVERY_PROIZOSLA_OSIBKA_BRO")); //ошибка
        }

        echo \Bitrix\Main\Web\Json::encode($result);
    }

    //запоминаем ID ПВЗ
    if ($request["action"] == 'setPvzId') {
        $fields      = \Bitrix\Main\Web\Json::decode($request->get("json"));
        $pvzId       = $fields['id'];
        $address     = $fields['address'];
        $name        = $fields['title'];

        $session->set('YD_PVZ_ID_SIMPLE', $pvzId);
        $session->set('YD_PVZ_ADDRESS', $address);
        $session->set('PVZ_FULLADDRESS', $name .', '. $address);

        $emptyFields = array();

        if ($session->has('PERSON_TYPE') && $session->get('PERSON_TYPE') > 0) {
            $options = TwinpxConfigTable::GetAllOptions(); //настройки
            $pTypeId = $session->get('PERSON_TYPE');
            /*$dbProps = CSaleOrderProps::GetList(array("SORT"=> "ASC"), array("IS_ADDRESS"    => "Y","ACTIVE"        => "Y","PERSON_TYPE_ID"=> $pTypeId), false, false, array());
            if ($prop = $dbProps->Fetch()) {
                $addressId = $prop['ID'];
            }*/
			//$emptyFields['PropAddress'] = ($addressId > 0 ) ? "ORDER_PROP_".$addressId : null;
			
            $emptyFields['PropAddress'] = ($options['PropAddress_'.$pTypeId] > 0) ? "ORDER_PROP_".$options['PropAddress_'.$pTypeId] : NULL;
        }

        $session->remove('PERSON_TYPE');

        echo \Bitrix\Main\Web\Json::encode(array("STATUS"=> 'Y', "FIELDS"=> $emptyFields));
    }
} 
else {
    echo GetMessage('Error');
}

