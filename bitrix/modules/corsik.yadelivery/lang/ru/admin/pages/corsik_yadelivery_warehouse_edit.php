<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_BACK' => 'Вернуться к списку складов',
        'CORSIK_DELIVERY_SERVICE_DELIVERY_WAREHOUSES' => 'Склад',
        'CORSIK_DELIVERY_SERVICE_DELIVERY_NOT_FOUND_WAREHOUSES' => 'Необходимо добавить хотя бы один склад на карту!',
        'CORSIK_DELIVERY_SERVICE_DELIVERY_POLYGONS' => 'Зона',
        'CORSIK_DELIVERY_SERVICE_PROPERTIES_WAREHOUSES' => 'Настройка стоимости доставки',
        'CORSIK_DELIVERY_SERVICE_LABEL_CHOOSE_ZONE' => 'Выберите зону',
        'CORSIK_DELIVERY_SERVICE_LABEL_SHOW' => 'Показать зону',
        'CORSIK_DELIVERY_SERVICE_LABEL_GEOJSON' => 'Загрузить данные из GeoJSON',
        'CORSIK_DELIVERY_SERVICE_HEADING_PRICE_WAREHOUSES' => 'Стоимость доставки по зонам:',
    ]);
}
