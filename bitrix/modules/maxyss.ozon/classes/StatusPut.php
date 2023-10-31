<?
class CMaxyssOzonStatusPut{
    static function tracking_number_set($lid, $postings){
        $arOptions = CMaxyssOzon::getOptions($lid);
        $arSettings = $arOptions[$lid];
        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $data_string = array(
            "tracking_numbers" => $postings
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $arResult = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, '/v2/fbs/posting/tracking-number/set');
//        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_status.txt", print_r($arResult, true) . PHP_EOL);

    }

    static function status_put($lid, $arStatusPut){
        $arOptions = CMaxyssOzon::getOptions($lid);
        $arSettings = $arOptions[$lid];
        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $link = array(
            'delivering' => "/v2/fbs/posting/delivering",
            'driver_pickup' => "/v2/fbs/posting/last-mile",
            'delivered' => "/v2/fbs/posting/delivered",
        );
        $bck = CMaxyssOzonAgent::bck();

        $OZON_FINAL_YES = CHelpMaxyss::chek_propety_order('OZON_FINAL_YES', $arSettings["PERSON_TYPE"], $lid);
        if ($bck['BCK'] && $bck['BCK'] != "Y" ) {

            foreach ($arStatusPut as $key => $status) {
                $postings = array();
                foreach ($status as $posting) {
                    $postings[] = $posting["posting_number"];
                }
                $data_string = array(
                    "posting_number" => $postings
                );
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $arResult = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, $link[$key]);
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_status.txt", print_r($arResult, true) . PHP_EOL, FILE_APPEND);

                if(!empty($arResult) && !isset($arResult['error']) && $key == 'delivered' && $OZON_FINAL_YES){

                    foreach ($arResult as $posting_ozon) {
                        $order_bitrix_id = $status[$posting_ozon["posting_number"]]["order_bitrix_id"];
                        if($order_bitrix_id > 0 && $posting_ozon["result"] == 1) {
                            $order = Bitrix\Sale\Order::load($order_bitrix_id);
                            $propertyCollection = $order->getPropertyCollection();
                            foreach ($propertyCollection as $prop) {
                                switch ($prop->getField('CODE')) {
                                    case 'OZON_FINAL_YES':
                                        $prop->setValue('Y');
                                        break;

                                    default:
                                }

                            };
                            $result = $order->save();
                        }
                    }
                }
            }
        }
    }

    static function ListOrderStatus($lid){
        $arOptions = CMaxyssOzon::getOptions($lid);
        $arSettings = $arOptions[$lid];

        $arStatusBy['awaiting_deliver'] = $arSettings["AWAITING_DELIVER"];
        $arStatusBy['delivering'] = $arSettings["DELIVERING"];
        $arStatusBy['delivered'] = $arSettings["DELIVERED"];
        $arStatusBy['driver_pickup'] = $arSettings["DRIVER_PICKUP"];

        $arStatusBy_revert = array_flip($arStatusBy);

        // find orders to Bitrix
        $arFilterOrder = array (
            "LID" => $lid,
            'PROPERTY_VAL_BY_CODE_OZON_FINAL_YES' => false,
            "@STATUS_ID" => array_values($arStatusBy),
            '@PROPERTY_VAL_BY_CODE_tpl_integration_type' => array('non_integrated', 'tpl_tracking', '3pl_tracking'),
        );
        $rsOrders = \CSaleOrder::GetList(
            array('DATE_INSERT' => 'DESC'),
            $arFilterOrder
        );

        $posting_info_tracking = array();
        $arStatusPut = array();

        while ($arOrder = $rsOrders->Fetch())
        {
            $posting_info = array();

            $order = Bitrix\Sale\Order::load($arOrder['ID']);
            $propertyCollection = $order->getPropertyCollection();
            foreach ($propertyCollection as $prop) {
                switch ($prop->getField('CODE')) {
                    case 'tpl_integration_type':
                        $tpl_integration_type = $prop->getValue();
                        break;

                    case $arSettings["PROPERTY_ORDER_OZON"]:
                        $posting_number = $prop->getValue();
                        break;

                    default:
                }

            }
            if($tpl_integration_type == 'non_integrated') {
                $posting_info = array(
                    "posting_number" => $posting_number,
                    "tpl_integration_type" => $tpl_integration_type,
                    "tracking_numbers" => $arOrder["TRACKING_NUMBER"],
                    "status" => $arStatusBy_revert[$arOrder["STATUS_ID"]],
                    "order_bitrix_id" => $arOrder["ID"],
                );
                if($arStatusBy_revert[$arOrder["STATUS_ID"]] != 'awaiting_deliver')
                    $arStatusPut[$tpl_integration_type][$arStatusBy_revert[$arOrder["STATUS_ID"]]][$posting_number] = $posting_info;
            }

            if($arOrder["TRACKING_NUMBER"] != ''){
                $posting_info_tracking[] = array(
                    "posting_number" => $posting_number,
                    "tracking_number" => $arOrder["TRACKING_NUMBER"],
                );
            }
        }

        if(!empty($arStatusPut['non_integrated'])){
           self::status_put($lid, $arStatusPut['non_integrated']);
        }

        if(!empty($posting_info_tracking)){
            self::tracking_number_set($lid, $posting_info_tracking);
        }

        return "CMaxyssOzonStatusPut::ListOrderStatus('".$lid."');";
    }

}