<?php
use	\Bitrix\Main,
	\Bitrix\Main\Entity,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Context,
	\Bitrix\Main\Application,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Sale,
	\Bitrix\Sale\Delivery,
	\Bitrix\Sale\Delivery\Services\Table,
	\Bitrix\Main\Page\Asset,
	\Bitrix\Main\Page\AssetLocation;
	
use \Twinpx\Yadelivery\TwinpxApi,
	\Twinpx\Yadelivery\TwinpxOfferTable,
	\Twinpx\Yadelivery\TwinpxConfigTable,
	\Twinpx\Yadelivery\TwinpxOfferTempTable;

Loader::includeModule("sale");

IncludeModuleLangFile(__FILE__);

class TwinpxEvent
{
    static $module_id = 'twinpx.yadelivery';

    //проверка перед сохранение корзины
    public static function OnSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        $thisData = time();
        $error    = array();
        $deliveryInfo = array();
        $request     = Context::getCurrent()->getRequest();
        $session     = Application::getInstance()->getSession();

        $delivery_id = $request->getPost("DELIVERY_ID"); //выбранная доставка
        $pay_id      = $request->getPost("PAY_SYSTEM_ID"); //платежная система

        //проверяем если наша доставка
        if ($delivery_id) {
            $arDelivery = TwinpxDelivery::ChechDelivery($delivery_id);
            if (!empty($arDelivery)) {
                $deliveryInfo = $arDelivery[$delivery_id];
            }
        }

        //если есть наша доставка
        if (!empty($deliveryInfo)) {
            $order        = $event->getParameter("ENTITY");
            $basketSumm   = $order->getBasePrice(); //сумма без скидок
            $deliverySumm = $order->getDeliveryPrice();
            $finalPrice   = intval($basketSumm - $deliverySumm);

            //передает адрес ПВЗ
            if ($deliveryInfo["CODE"] == 'twpx_yadelivery:pickup_simple' || $deliveryInfo["CODE"] == 'twpx_yadelivery:pickup') {
                $propertyCollection = $order->getPropertyCollection();
                if (!empty($propertyCollection)) {
                    foreach ($propertyCollection as $propertyItem) {
                        switch ($propertyItem->getField("CODE")) {
                            case 'YD_PVZ':
                            $propertyItem->setField("VALUE", $session->get('PVZ_FULLADDRESS'));
                            break;
                        }
                    }
                }
            }

            //если доставка ПВЗ с согласование
            if ($deliveryInfo["CODE"] === "twpx_yadelivery:pickup_simple") {
                if ($session->get('YD_PVZ_ID_SIMPLE') == '' || !$session->has('YD_PVZ_ID_SIMPLE')) {
                    $error[] = Loc::GetMessage('Select-Pickup');
                }
                elseif ($session->get('PICKUP_SIMPLE_NOTCALCULATE') == 1) {
                    $error[] = Loc::GetMessage('Price-Nocalculate');
                }
                else {
                    return;
                }
            }
            //если доставка курьеров с согласование
            elseif ($deliveryInfo["CODE"] === "twpx_yadelivery:curier_simple") {
                if ($session->get('CURIER_SIMPLE_NOTCALCULATE') == 1) {
                    $error[] = Loc::GetMessage('Price-Nocalculate');
                }
                else {
                    return;
                }
            }
            //проверка если данные не менялсь
            elseif (!$session->has('YD_OFFER_ID') || $session->get('YD_OFFER_ID') == '') {
                $error[] = ($deliveryInfo["CODE"] === 'twpx_yadelivery:pickup') ? Loc::GetMessage('Changes-Property-Pickup') : Loc::GetMessage('Changes-Property-Curier');
            }
            //если цена не задана
            elseif ($session->get('YD_CURIERPRICE') == '' && $session->get('YD_PVZPRICE') == '') {
                $error[] = Loc::GetMessage('Select-Interval');
            }
            //проверка на изменение цены
            elseif ($session->has('YD_BASKET_SUMM') && $finalPrice != intval($session->get('YD_BASKET_SUMM'))) {
                $session->remove('YD_BASKET_SUMM');
                $session->remove('YD_OFFER_ID');
                $error[] = Loc::GetMessage('Sum-Basket-Change');
            }
            //если тип оплаты не менялса
            elseif ($session->get('YD_PAY_ID') != $pay_id) {
                $session->remove('YD_PAY_ID');
                $session->remove('YD_OFFER_ID');
                $error[] = Loc::GetMessage('Pay-System-Change');
            }
            //если тип достаки не менялса
            elseif ($session->get('YD_DELIVERY_ID') != $delivery_id) {
                $session->remove('YD_DELIVERY_ID');
                $session->remove('YD_OFFER_ID');
                $error[] = Loc::GetMessage('Delivery-Change');
            }
            //проверяем время жизни оффера
            elseif ($session->has('YD_SETPRICE')) {
                $expire = strtotime($session->get('YD_SETPRICE'));
                if ($expire < $thisData) {
                    $error[] = Loc::GetMessage('Offer-Expire');
                    $session->remove('YD_SETPRICE');
                    $session->remove('YD_OFFER_ID');
                }
            }
            //если нет сессий тоже выдем ошибку
            else {
                $error[] = Loc::GetMessage('Offer-Expire');
            }

            // выполняем код перед сохранением, но после проверки полей
            if (!empty($error)) {
                return new \Bitrix\Main\EventResult(Main\EventResult::ERROR, Sale\ResultError::create(new Main\Error($error, "GRAIN_IMFAST")));
            }
            else {
                return;
            }
        }
        else {
            return;
        }
    }

    //изменение оплаты
    public static function OnSaleOnSalePayOrder($id, $value)
    {
        if ($value == 'Y') {
            $res = TwinpxOfferTempTable::getList(array(
                    'select' => array('ID'),
                    'filter' => array('ORDER_ID'=>$id)
                ));
            if ($ar = $res->Fetch()) {
                TwinpxOfferTempTable::update($ar['ID'], array('PAYCONFIRM'=> 1)); //подверждаем заказ
            }
        }
    }

	//ссылка в меню закза
    public static function OnAdminContextMenuShow(&$items)
    {
        global $module_id;
        global $APPLICATION;
        //add custom button to the index page toolbar
        if ($GLOBALS["APPLICATION"]->GetCurPage(true) == "/bitrix/admin/sale_order_view.php" && intval($_REQUEST['ID']) > 0) {
			CUtil::InitJSCore( array('twinpx_admin_lib')); //подключаем стили и скрипты
            $items[] = array(
                "TEXT"=> GetMessage('TWINPX_YADELIVERY'),
                "ICON"=> "twinpx_icon",
                "MENU" => array(
                    array(
                        "TEXT"   => GetMessage('TWINPX_CURIER'),
                        "LINK"   => "javascript:void(0)",
                        "ONCLICK"=> "newDelivery({$_REQUEST['ID']})",
                    ),
                    array(
                        "TEXT"   => GetMessage('TWINPX_PICKUP'),
                        "LINK"   => "javascript:void(0)",
                        "ONCLICK"=> "newDeliveryPvz({$_REQUEST['ID']})",
                    ),
                ),
            );
            //\Bitrix\Main\UI\Extension::load("ui.buttons");
            $request = Main\Application::getInstance()->getContext()->getRequest();
            $scheme  = $request->isHttps() ? 'https' : 'http';
            $ya_key  = htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''));
			if($ya_key == '') Asset::getInstance()->addString('<script>window.twinpxYadeliveryYmapsAPI = false;</script>');
            $APPLICATION->AddHeadString('<script src="'.$scheme.'://api-maps.yandex.ru/2.1.50/?apikey='.$ya_key.'&load=package.full&lang=ru-RU"></script>', true); //подключаем карту
            $session = Application::getInstance()->getSession();
            $session->remove('YD_CURIER_PRICE');
            $session->remove('YD_PICKUP_PRICE');
            ?>
            <script>
                window.twinpxYadeliveryFetchURL = '/bitrix/tools/<?=self::$module_id?>/admin/ajax.php';
            </script>
            <?
        }

    }

	//подключение карты в конце страницы
	public static function OnEndBuffer(&$content)
	{
		global $APPLICATION;
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $ya_key  = htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''));
        $disable = TwinpxConfigTable::getByCode('DisableMaps'); //настройки       
        if(strlen($ya_key) > 1 && $disable != 'Y' && !$request->isAdminSection()){
			$content = str_replace("</body>", "<script src=\"//api-maps.yandex.ru/2.1.50/?apikey=".$ya_key."&load=package.full&lang=ru-RU\" id=\"twpx\"></script>\n</body>", $content);
		}
		return $content;
	}
}