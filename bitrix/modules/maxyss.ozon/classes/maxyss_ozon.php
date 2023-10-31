<?
class CRestQuery{
    static function rest_query($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $data_string, $path){
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "Client-Id: " . $ClientId,
                    "Api-Key: " . $ApiKey,
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string)
                )
            )
        ]);


        $str_result = $api->post($path, []);
//        echo '<pre>', print_r($str_result), '</pre>' ;
        if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($str_result)));
        }

        if ($str_result->info->http_code == 200) {

            if($str_result->headers->content_type == 'application/pdf'){
                $pdf_stream = $str_result->response;
                file_put_contents($_SERVER['DOCUMENT_ROOT']."/upload/package-label.pdf", $pdf_stream);
                echo json_encode(array('success' => '/upload/package-label.pdf'));

            }else{
                $arRes = \Bitrix\Main\Web\Json::decode($str_result->response);
                $res = $arRes['result'];
                return $res;
            }

        } else {
            if($str_result->response !='') {
                if ( $str_result->decode_response()->code == 3 ) {
                    $res = array('error' => $str_result->decode_response()->message);
                    return $res;

                } elseif ($str_result->decode_response()->error->code == "BAD_REQUEST") {

                    if ($str_result->info->url == "HTTP://api-seller.ozon.ru/v2/posting/fbs/ship") {
                        $res = \Bitrix\Main\Web\Json::decode($str_result->response);
                    } else {
                        $res = array('error' => $str_result->decode_response()->error->message);
                    }
                    return $res;
                }else{
                    $res = array('error' => $str_result->decode_response()->error->code.$str_result->decode_response()->error->message.$str_result->decode_response()->message);
                    return $res;
                }
            }
            else
            {
                $res = array('error' => $str_result->error);
                return $res;
            }
        }
    }
}
class CHelpMaxyss{
    static  function arr_to_file($filename, $array){
            $arr = serialize($array);
            file_put_contents($filename, $arr);
            return true;
    }
    static  function arr_from_file($filename){
        if(file_exists($filename)) {
            $arr = file_get_contents($filename);
            $arr = unserialize($arr);
            return $arr;
        }else{
            return false;
        }
    }
    static function chek_propety_order($prop_code = '', $person_type_id = '', $lid = ''){
        $res = false;
        if($prop_code !== '' && $lid != '' && $person_type_id !=''){

            $arFields = array(
                "PERSON_TYPE_ID" => $person_type_id,
                "NAME" => $prop_code,
                "TYPE" => "STRING",
                "REQUIED" => 'N',
                "MULTIPLE" => "N",
                "SORT" => '100',
                "USER_PROPS" => "N",
                "IS_LOCATION" => "N",
                "CODE" => $prop_code,
                "IS_FILTERED" => 'Y',
                'ACTIVE' => "Y",
                "UTIL" => "Y",
                "DEFAULT_VALUE"=>'',
                "IS_EMAIL" => "N",
                "IS_PROFILE_NAME" => "N",
                "IS_PAYER" => "N",
                "IS_ADDRESS" => "N",
                "IS_PHONE" => "N",
            );

            $db_props = CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "PERSON_TYPE_ID" => $person_type_id,
                    "=CODE" => $prop_code,
                ),
                false,
                false,
                array()
            );

            if (!$props = $db_props->Fetch()){
                $db_propsGroup = CSaleOrderPropsGroup::GetList(
                    array("SORT" => "ASC"),
                    array("PERSON_TYPE_ID" => $person_type_id),
                    false,
                    false,
                    array()
                );

                if ($propsGroup = $db_propsGroup->Fetch())
                {
                    $arFields["PROPS_GROUP_ID"] = $propsGroup["ID"];
                }else{
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", 'NO GROUPE PROPS ID FOR ORDER' . PHP_EOL, FILE_APPEND);
                }
                $ID = CSaleOrderProps::Add($arFields);
                if($ID > 0)
                    $res = $prop_code;
            }else{
                $res = $prop_code;
            }

        }
        return $res;
    }
}


class FilterCustomOzon
{
    public function __construct($params = array())
    {
        $params = array();
    }

    public function parseCondition($condition, $params)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            if ($condition['CLASS_ID'] === 'CondGroup')
            {
                if (!empty($condition['CHILDREN']))
                {
                    foreach ($condition['CHILDREN'] as $child)
                    {
                        $childResult = $this->parseCondition($child, $params);

                        // is group
                        if ($child['CLASS_ID'] === 'CondGroup')
                        {
                            $result[] = $childResult;
                        }
                        // same property names not overrides each other
                        elseif (isset($result[key($childResult)]))
                        {
                            $fieldName = key($childResult);

                            if (!isset($result['LOGIC']))
                            {
                                $result = array(
                                    'LOGIC' => $condition['DATA']['All'],
                                    array($fieldName => $result[$fieldName])
                                );
                            }

                            $result[][$fieldName] = $childResult[$fieldName];
                        }
                        else
                        {
//                            $result += $childResult;
                            $result[]= $childResult;
                        }
                    }
                    if (!empty($result))
                    {
                        $this->parsePropertyCondition($result, $condition, $params);

                        if (count($result) > 1)
                        {
                            $result['LOGIC'] = $condition['DATA']['All'];
                        }
                    }
                }
            }
            else
            {
                $result += $this->parseConditionLevel($condition, $params);
            }
        }

        return $result;
    }
    protected function parseConditionOperator($condition)
    {
        $operator = '';

        switch ($condition['DATA']['logic'])
        {
            case 'Equal':
                $operator = '';
                break;
            case 'Not':
                $operator = '!';
                break;
            case 'Contain':
                $operator = '%';
                break;
            case 'NotCont':
                $operator = '!%';
                break;
            case 'Great':
                $operator = '>';
                break;
            case 'Less':
                $operator = '<';
                break;
            case 'EqGr':
                $operator = '>=';
                break;
            case 'EqLs':
                $operator = '<=';
                break;
        }

        return $operator;
    }
    protected function parseConditionValue($condition, $name)
    {
        $value = $condition['DATA']['value'];

        switch ($name)
        {
            case 'DATE_ACTIVE_FROM':
            case 'DATE_ACTIVE_TO':
            case 'DATE_CREATE':
            case 'TIMESTAMP_X':
                $value = ConvertTimeStamp($value, 'FULL');
                break;
        }

        return $value;
    }

    protected function parseConditionLevel($condition, $params)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            $name = $this->parseConditionName($condition);
            if (!empty($name))
            {
                $operator = $this->parseConditionOperator($condition);
                $value = $this->parseConditionValue($condition, $name);
                $result[$operator.$name] = $value;

                if ($name === 'SECTION_ID')
                {
                    $result['INCLUDE_SUBSECTIONS'] = isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'N' ? 'N' : 'Y';

                    if (isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'A')
                    {
                        $result['SECTION_GLOBAL_ACTIVE'] = 'Y';
                    }

                    $result = array($result);
                }
            }
        }

        return $result;
    }

    protected function parseConditionName(array $condition)
    {
        $name = '';
        $conditionNameMap = array(
            'CondIBXmlID' => 'XML_ID',
            'CondIBSection' => 'SECTION_ID',
            'CondIBDateActiveFrom' => 'DATE_ACTIVE_FROM',
            'CondIBDateActiveTo' => 'DATE_ACTIVE_TO',
            'CondIBSort' => 'SORT',
            'CondIBDateCreate' => 'DATE_CREATE',
            'CondIBCreatedBy' => 'CREATED_BY',
            'CondIBTimestampX' => 'TIMESTAMP_X',
            'CondIBModifiedBy' => 'MODIFIED_BY',
            'CondIBTags' => 'TAGS',
            'CondCatQuantity' => 'QUANTITY',
            'CondCatWeight' => 'WEIGHT'
        );

        if (isset($conditionNameMap[$condition['CLASS_ID']]))
        {
            $name = $conditionNameMap[$condition['CLASS_ID']];
        }
        elseif (mb_strpos($condition['CLASS_ID'], 'CondIBProp') !== false)
        {
            $name = $condition['CLASS_ID'];
        }

        return $name;
    }

    protected function parsePropertyCondition(array &$result, array $condition, $params)
    {
        if (!empty($result))
        {
            $subFilter = array();

            foreach ($result as $name => $value)
            {
                if (!empty($result[$name]) && is_array($result[$name]))
                {
                    $this->parsePropertyCondition($result[$name], $condition, $params);
                }
                else
                {
                    if (($ind = mb_strpos($name, 'CondIBProp')) !== false)
                    {
                        list($prefix, $iblock, $propertyId) = explode(':', $name);
                        $operator = $ind > 0? mb_substr($prefix, 0, $ind) : '';

                        $catalogInfo = \CCatalogSku::GetInfoByIBlock($iblock);
                        if (!empty($catalogInfo))
                        {
                            if (
                                $catalogInfo['CATALOG_TYPE'] != \CCatalogSku::TYPE_CATALOG
                                && $catalogInfo['IBLOCK_ID'] == $iblock
                            )
                            {
                                $subFilter[$operator.'PROPERTY_'.$propertyId] = $value;
                            }
                            else
                            {
                                $result[$operator.'PROPERTY_'.$propertyId] = $value;
                            }
                        }

                        unset($result[$name]);
                    }
                }
            }

            if (!empty($subFilter) && !empty($catalogInfo))
            {
                $offerPropFilter = array(
                    'IBLOCK_ID' => $catalogInfo['IBLOCK_ID'],
                    'ACTIVE_DATE' => 'Y',
                    'ACTIVE' => 'Y'
                );

                if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y')
                {
                    $offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
                }
                elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L')
                {
                    $offerPropFilter[] = array(
                        'LOGIC' => 'OR',
                        'AVAILABLE' => 'Y',
                        'SUBSCRIBE' => 'Y'
                    );
                }

                if (count($subFilter) > 1)
                {
                    $subFilter['LOGIC'] = $condition['DATA']['All'];
                    $subFilter = array($subFilter);
                }

                $result['=ID'] = \CIBlockElement::SubQuery(
                    'PROPERTY_'.$catalogInfo['SKU_PROPERTY_ID'],
                    $offerPropFilter + $subFilter
                );
            }
        }
    }

    public function onPrepareComponentParams($params)
    {

        if (isset($params['CUSTOM_FILTER']) && is_string($params['CUSTOM_FILTER'])) {
            try {
                $params['CUSTOM_FILTER'] = $this->parseCondition(Json::decode($params['CUSTOM_FILTER']), $params);
            } catch (\Exception $e) {
                $params['CUSTOM_FILTER'] = array();
            }
        } else {
            $params['CUSTOM_FILTER'] = array();
        }
    }
}

?>