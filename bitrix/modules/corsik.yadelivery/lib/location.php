<?php

namespace Corsik\YaDelivery;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Config\Option,
    \Bitrix\Sale\Location as BxLocation,
    \Bitrix\Sale\Delivery\Restrictions\ByLocation;

class Location
{

    private $log_path = "";

    public function __construct()
    {
        $module_id = 'corsik.yadelivery';
        $this->log_path = Option::get($module_id, "log_path");

        Loader::includeModule('sale');
        Loader::includeModule($module_id);
    }

    /**
     * @param $post
     * @return array|bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    function getLocation($post)
    {
        array_walk($post, [$this, 'unEscape']);
        $delivery = $post['DELIVERY'];
        unset($post['DELIVERY']);

        if (\CSaleLocation::isLocationProEnabled()) {
            $arResult = $this->getLocationRecursive($post);
            if (!$arResult) {
                Handler::writeToFile($post, 'Location', $this->log_path);
            }

            foreach ($delivery as $d) {
                if (Handler::checkLocation($arResult['CODE'], $d)) {
                    $arResult['DELIVERY'] = $d;
                    break;
                } else {
                    $arResult['DELIVERY'] = 'N';
                }
            }
        } else {
            $arResult = false;
        }
        return $arResult;
    }

    /**
     * Рекурсивно проверяем все местоположения по типам
     * @param $post
     * @return array
     */
    function getLocationRecursive($post): array
    {
        if (!empty($post['CITY']['query']) || !empty($post['SUBREGION']['query'])) {
            if (!empty($post['CITY']['query'])) {
                $query = $post['CITY']['query'];
                $type = $post['CITY']['type'];
                $options = $post['SUBREGION']['query'] ?? $post['REGION']['query'];
            } elseif (!empty($post['SUBREGION']['query'])) {
                $query = $post['SUBREGION']['query'];
                $type = $post['SUBREGION']['type'];
                $options = $post['REGION']['query'];
            }
        } elseif ($post['REGION']['query']) {
            $query = "%{$post['REGION']['query']}";
            $type = $post['REGION']['type'];
        }
        
        $result = $this->getLocCode($query, $type, $options);
        
        if (!$result) {
            array_shift($post);
            return $this->getLocationRecursive($post);
        }else{
            return $result;
        }
    }

    /**
     * Получаем код местоположения
     * @param $needle
     * @param $type
     * @param null $options
     * @return array|bool|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getLocCode($needle, $type, $options = null)
    {
        $type = $this->getLocType($type);
        $filter = ['NAME.NAME' => $needle, 'TYPE_ID' => $type, '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID];
        if ($options) {
            $option = explode(' - ', $options);
            $filter = array_merge($filter, ['?PARENTS.NAME.NAME' => '%' . $option[0]]);
        }
        $select = ['CODE'];
        $code = BxLocation\LocationTable::getList(['filter' => $filter, 'select' => $select])->fetchRaw();
        return $code ?? false;
    }

    /*
     * Получаем тип местоположения
     */
    private function getLocType($type)
    {
        return BxLocation\TypeTable::getList(['filter' => ['=CODE' => $type], 'select' => ['ID']])->fetch();
    }

    private function unEscape(&$post, $key)
    {
        global $APPLICATION;
        if ($key !== 'DELIVERY' && $post) {
            $post = [
                'type' => $APPLICATION->UnJSEscape(trim($key)),
                'query' => $APPLICATION->UnJSEscape(trim($post)) . '%'
            ];
        }
    }

}
