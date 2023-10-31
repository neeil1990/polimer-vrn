<?php

namespace Corsik\YaDelivery;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Location as BxLocation;
use CSaleLocation;

class Core
{
    public string $module_id = 'corsik.yadelivery';
    private $handler;
    private string $log_path = "";

    public function __construct()
    {
        $this->handler = Handler::getInstance();
        $this->log_path = Option::get($this->module_id, "log_path");
        Loader::includeModule('sale');
        Loader::includeModule($this->module_id);
    }

    public function init($post)
    {
        $result = null;
        switch ($post['action']) {
            case 'init':
                $result = $this->handler->getModuleParameters();
                break;
            case 'location':
                $result = $this->getLocation($post);
                break;
            case 'getCoords':
                $result = $this->handler->getMapCoords($post);
                break;
            case 'calculate':
                $result = $this->handler->calculatePrice($post);
                break;
        }
        return $result;
    }

    /**
     * @param $post
     * @return array|bool
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    function getLocation($post)
    {
        $post = $this->unEscape($post);
        $deliveries = $post['DELIVERY'];
        $personType = $post['PERSON_TYPE'];
        $isEnableAutoLocation = Options::getBoolOptionByName("enable_location_options_$personType");

        $result = [
            'SUCCESS' => $isEnableAutoLocation
        ];

        if (!CSaleLocation::isLocationProEnabled() || !is_array($deliveries)) {
            return [
                'ERROR' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_LOCATIONS_ERROR"),
                'SUCCESS' => false,
            ];
        }

        $locationData = $this->getLocationRecursive($post);

        if (empty($locationData)) {
            Debug::writeToFile($post, "(" . date("d.m.Y H:i:s") . ") Location", $this->log_path);
            $result['ERROR'] = Loc::getMessage("CORSIK_DELIVERY_SERVICE_LOCATION_NOT_FOUND");
            return $result;
        }


        foreach ($deliveries as $delivery) {
            if (!$this->handler::checkLocation($locationData['CODE'], $delivery)) {
                return [
                    'ERROR' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_LOCATIONS_BAN"),
                    'SUCCESS' => false,
                ];
            }

            if (!$isEnableAutoLocation) {
                $result['ERROR'] = Loc::getMessage("CORSIK_DELIVERY_SERVICE_LOCATION_NOT_ENABLE");
            }

            $result = array_merge($result, $locationData);
        }

        return $result;

    }

    /**
     * @param $post
     * @param $key
     */
    private function unEscape($post)
    {
        global $APPLICATION;
        $result = [];
        foreach ($post as $key => $val) {
            if ($key === 'DELIVERY' || $key === 'PERSON_TYPE') {
                $result[$key] = $val;
            } else if (!boolval($val) || $key === 'action') {
                unset($post[$key]);
            } else {
                $val = preg_replace_callback('/\/(.+?)\//',
                    function ($matches) {
                        return "($matches[1])";
                    }, trim($val)
                );
                $result[$key] = [
                    'type' => $APPLICATION->UnJSEscape(trim($key)),
                    'query' => $APPLICATION->UnJSEscape($val) . '%'
                ];
            }
        }
        return $result;
    }

    /**
     * Рекурсивно проверяем все местоположения по типам
     * @param $post
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    function getLocationRecursive($post): array
    {
        if (!empty($post['CITY']['query']) || !empty($post['SUBREGION']['query'])) {
            if (!empty($post['CITY']['query'])) {
                $query = $post['CITY']['query'];
                $type = $post['CITY']['type'];
                $options = $post['SUBREGION']['query'] ?? $post['REGION']['query'];
            } else if (!empty($post['SUBREGION']['query'])) {
                $query = $post['SUBREGION']['query'];
                $type = $post['SUBREGION']['type'];
                $options = $post['REGION']['query'];
            }
        } else if ($post['REGION']['query']) {
            $query = "%{$post['REGION']['query']}";
            $type = $post['REGION']['type'];
        } else if (empty($post)) {
            return [];
        }

        $result = $this->getLocCode($query, $type, $options);
        if (!$result && !empty($post)) {
            array_shift($post);
            return $this->getLocationRecursive($post);
        } else {
            return $result;
        }
    }

    /**
     * Получаем код местоположения
     * @param $needle
     * @param $type
     * @param null $options
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getLocCode($needle, $type, $options = null): array
    {
        $type = $this->getLocType($type);
        $filter = ['NAME.NAME' => $needle, 'TYPE_ID' => $type, '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID];
        if ($options) {
            $option = explode(' - ', $options);
            $filter = array_merge($filter, ['?PARENTS.NAME.NAME' => '%' . $option[0]]);
        }
        $select = ['CODE', 'ID', 'NAME'];
        $code = BxLocation\LocationTable::getList(['order' => ['ID' => 'DESC'], 'filter' => $filter, 'select' => $select])->fetchRaw();
        return $code ?: [];
    }

    /**
     * Получаем тип местоположения
     * @param $type
     * @return array|false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getLocType($type)
    {
        return BxLocation\TypeTable::getList(['filter' => ['=CODE' => $type], 'select' => ['ID']])->fetch();
    }
}
