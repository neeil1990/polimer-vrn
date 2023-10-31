<?

use Sotbit\Seometa\Orm\ConditionTable;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

class CSeoMetaTagsProperty extends
    CSeoMetaTags
{
    public static $params = array();

    /**
     * function collect values of property that in furute won't find in database
     * @param string $propertyCode
     * @param array $array - there are necessary fields of property
     * */
    private function addFilterValue(
        $propertyCode,
        $array
    ) {
        if (is_array(self::$FilterResult['ITEMS'])) {
            foreach (self::$FilterResult['ITEMS'] as $idProrepty => &$Property) {
                if ($Property['CODE'] == $propertyCode) {
                    if (!isset($Property['VALUES'][$array['VALUE']])) {
                        $Property['VALUES'][$array['VALUE']] = $array;
                    }
                }
            }
        } else {
            self::$FilterResult['ITEMS'][$propertyCode]['VALUES'][$array['VALUE']] = $array;
        }
    }

    /**
     * find value of property from FilterResult by field with name CONTROL_NAME_SEF, if we don't find value, do query to database and save in FilterResult
     * @param string $propertyCode
     * @param string $sefCode
     * @return string
     * */
    private function getPropertyValueBySef(
        $propertyCode,
        $sefCode
    ) {
        if (empty($sefCode)) {
            return '';
        }

        if (is_array(self::$FilterResult['ITEMS'])) {
            foreach (self::$FilterResult['ITEMS'] as $idProrepty => $Property) {
                if ($Property['CODE'] == $propertyCode) {
                    foreach ($Property['VALUES'] as $idVal => $Value) {
                        if ($Value['CONTROL_NAME_SEF'] == $sefCode) {
                            return $Value['VALUE'];
                        }
                    }
                }
            }
        }

        return $sefCode;
    }

    public function calculate(
        array $parameters
    ) {
        $return = array();
        $Property = $parameters;
        $codes = array();

        if (empty(parent::$FilterResult['ITEMS'])) {
            foreach ($Property as $prop) {
                if (isset(self::$params[$prop])) {
                    if (is_array(self::$params[$prop])) {
                        foreach (self::$params[$prop] as $pr) {
                            $return[] = self::getPropertyValueBySef($prop,
                                $pr);
                        }
                    } else {
                        $return[] = self::getPropertyValueBySef($prop,
                            self::$params[$prop]);
                    }
                }
            }

            return $return;
        }

        if (!empty(self::$params)) {
            foreach (self::$params as $code => $values) {
                if (in_array($code,
                    $Property)) {
                    foreach (parent::$FilterResult['ITEMS'] as $key => $elements) {
                        if ($elements['CODE'] == $code) {
                            foreach ($values as $sef_name) {
                                foreach ($elements['VALUES'] as $key_val => $value) {
                                    if (strcmp(\CUtil::translit($value['VALUE'],
                                            'ru',
                                            array(
                                                "replace_space" => "-",
                                                "replace_other" => "-"
                                            )),
                                            $sef_name) == 0 || strcmp($value['VALUE_ID'],
                                            $sef_name) == 0 || $key_val == $sef_name) {
                                        $return[] = $value['VALUE'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif (is_array($Property)) {
            foreach (parent::$FilterResult['ITEMS'] as $key => $elements) {
                foreach ($Property as $prop) {
                    if ($prop == $elements['CODE'] && !isset($codes[$elements['CODE']])) {
                        $codes[$elements['CODE']] = "Y";
                        foreach ($elements['VALUES'] as $key_element => $element) {
                            if ($element['CHECKED'] == 1) {
                                if ($elements['PROPERTY_TYPE'] == 'S' && $elements['USER_TYPE'] == 'directory' && $element['VALUE']) //hak for HL because isset LIST_TYPE = ID
                                {
                                    $return[] = $element['VALUE'];
                                } else {
                                    if (isset($element['LIST_VALUE_NAME'])) {
                                        $return[] = $element['LIST_VALUE_NAME'];
                                    } elseif (isset($element['LIST_VALUE'])) {
                                        $return[] = $element['LIST_VALUE'];
                                    } else {
                                        $return[] = $element['VALUE'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            foreach (parent::$FilterResult['ITEMS'] as $key => $elements) {
                if ($Property == $elements['CODE'] && !isset($codes[$elements['CODE']])) {
                    $codes[$elements['CODE']] = "Y";
                    foreach ($elements['VALUES'] as $key_element => $element) {
                        if ($element['CHECKED'] == 1) {
                            if (isset($element['LIST_VALUE'])) {
                                $return[] = $element['LIST_VALUE'];
                            } else {
                                $return[] = $element['VALUE'];
                            }
                        }
                    }
                }
            }
        }

        if (empty($return)) {
            foreach ($Property as $prop) {
                if (isset(self::$params[$prop])) {
                    if (is_array(self::$params[$prop])) {
                        foreach (self::$params[$prop] as $pr) {
                            $return[] = self::getPropertyValueBySef($prop,
                                $pr);
                        }
                    } else {
                        $return[] = self::getPropertyValueBySef($prop,
                            self::$params[$prop]);
                    }
                }
            }
        }

        if (empty($return)) {
            if(!empty(self::$params)){
                foreach (self::$params as $item) {
                    if (isset($item['FROM'])) {
                        $return[] = $item['FROM'];
                    }
                    if (isset($item['TO']) && $item['FROM'] != $item['TO']) {
                        $return[] = $item['TO'];
                    }
                }
            }
        }

        return $return;
    }
}

class first_upper extends
    CSeoMetaTags
{
    public function calculate(
        array $parameters
    ) {
        return array_map(
            function (
                $item
            ) {
                return mb_strtoupper(mb_substr($item, 0, 1)) . mb_substr($item, 1);
            },
            $parameters
        );
    }
}

class nonfirst extends
    CSeoMetaTags
{
    public function calculate(
        array $parameters
    ) {
        return (!isset($_REQUEST['PAGEN_1']) || $_REQUEST['PAGEN_1'] == 1) ? '' : $parameters;
    }
}

class iffilled extends
    CSeoMetaTags
{
    public function calculate(
        array $parameters
    ) {
        if (isset($parameters[0]) && $parameters[0] && isset($parameters[1])) {
            return sprintf($parameters[1],
                $parameters[0]) ?: '';
        }

        return '';
    }
}

class prop_list extends
    CSeoMetaTags
{
    public function calculate(
        array $parameters
    ) {
        $result = '';
        $arItems = self::$FilterResult['ITEMS'];
        if ($arItems && is_array($arItems)) {
            foreach ($arItems as $item) {
                if($item['VALUES']) {
                    $arFiltered = [];
                    foreach ($item['VALUES'] as $element) {
                        if($element['CHECKED'] === true || $element['CHECKED'] === 1) {
                            $arFiltered[] = $element[$parameters[2] ?: 'VALUE'];
                        }
                    }

                    if($arFiltered) {
                        if ($parameters[0]) {
                            $result .= $item[$parameters[0]];
                            $result .= $parameters[2] ? ': ' : ($parameters[1] ?: ',');
                        }

                        if ($parameters[2]) {
                            $result .= implode($parameters[1] ?: ', ',$arFiltered);
                        }

                        if($result) {
                            $result .= ' ';
                        }
                    }
                }
            }
        }

        return $result;
    }
}

?>