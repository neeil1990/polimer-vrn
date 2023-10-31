<?
class CMaxyssGetOzonInfo{
    public static function downloadIdOzon($lid='', $limit = 300, $last_id = ''){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $data_string = array(
            "filter" => array(
                "visibility"=>"MODERATED"
            ),
            "last_id"=>$last_id,
            "limit"=>$limit
        );

//                $arProductOzon = self::getQueryOzonRecursive($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");
        $arResultOzon = self::getQueryOzon($ClientId, $ApiKey.'', $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");
//        echo '<pre>', print_r($arResultOzon), '</pre>' ;
        if(isset($arResultOzon['count_item']) && $arResultOzon['count_item'] == $limit){
            return array('go_run'=>true, 'last_id'=>$arResultOzon['last_id'], 'items'=>$arResultOzon['items']);
        }elseif($arResultOzon['message']){
            return array('go_run'=>false, 'mess'=>$arResultOzon['message']);
        }
        else
        {
            return array('go_run'=>false, 'mess'=>'end', 'items'=>$arResultOzon['items']);
        }
    }

    public static function getQueryOzon($ClientId, $ApiKey, $base_url, $data_string, $path){
        $data_string_json = \Bitrix\Main\Web\Json::encode($data_string);
        $arProduct = array();
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string_json,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "Client-Id: " . $ClientId,
                    "Api-Key: " . $ApiKey,
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string_json)
                )
            )
        ]);
        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 200) {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }
        else
        {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }

        if(!empty($arProduct['result']['items'])){
            // запустим обновление свойства элементов
            return array('count_item'=>count($arProduct['result']['items']), 'last_id'=>$arProduct['result']['last_id'], 'items'=>$arProduct['result']['items']);
        }
        else{
            return array('message'=>$arProduct['message']);
        }
    }

    public static function getQueryOzonRecursive($ClientId, $ApiKey, $base_url, $data_string, $path, $arProductOzon=array()){
        $data_string_json = \Bitrix\Main\Web\Json::encode($data_string);
        $arProduct = array();
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string_json,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "Client-Id: " . $ClientId,
                    "Api-Key: " . $ApiKey,
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string_json)
                )
            )
        ]);
        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 200) {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }
        if(!empty($arProduct['result']['items']))
            $arProductOzon = array_merge($arProductOzon, $arProduct['result']["items"]);

        if(count($arProductOzon) < $arProduct['result']['total']){
            $data_string["last_id"] = $arProduct['result']['last_id'];
            $arProductOzon = self::getQueryOzonRecursive($ClientId, $ApiKey, $base_url, $data_string, $path, $arProductOzon);
            return $arProductOzon;
        }
        return $arProductOzon;
    }
}