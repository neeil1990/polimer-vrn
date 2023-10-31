<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_BACK' => 'Вернуться к списку зон',
        'CORSIK_DELIVERY_SERVICE_PROPERTIES_ZONES' => 'Настройка зон',
        'CORSIK_DELIVERY_SERVICE_SAVE_BTN_ZONE' => 'Добавить зону доставки',
        'CORSIK_DELIVERY_SERVICE_LABEL_SHOW_ZONE' => 'Просмотреть зону',
        'CORSIK_DELIVERY_SERVICE_LABEL_POLYGON' => 'Загрузить зону из GeoJSON',
        'CORSIK_DELIVERY_SERVICE_LABEL_NO_NAME' => 'Отсутствует имя зоны',
        'CORSIK_DELIVERY_SERVICE_LABEL_SAVE_MAP' => "Сохранить карту зон",
        'CORSIK_DELIVERY_SERVICE_LABEL_CLEAR_ALL' => "Очистить все зоны",
        'CORSIK_DELIVERY_SERVICE_HEADING_PRICE_ZONES' => 'Стоимость доставки за пределами зоны:',
        'CORSIK_DELIVERY_SERVICE_ZONE_PRICE' => 'Настройка стоимости доставки',
        'CORSIK_DELIVERY_SERVICE_LABEL_KM' => " км",
        'CORSIK_DELIVERY_SERVICE_LABEL_COST' => 'Цена за км. (руб.)',
        'CORSIK_DELIVERY_SERVICE_LABEL_COUNT_KM' => 'Количество км.',
        'CORSIK_DELIVERY_SERVICE_ZONES_RESTRICTION' => 'Ограничения доставки',
        'CORSIK_DELIVERY_SERVICE_HEADING_ZONES_RESTRICTION' => 'Ограничения доставки для определённых зоны:',
        'CORSIK_DELIVERY_SERVICE_MAX_ROUTE_LENGTH' => 'Максимальная длина маршрута в км.',
    ]);
}
