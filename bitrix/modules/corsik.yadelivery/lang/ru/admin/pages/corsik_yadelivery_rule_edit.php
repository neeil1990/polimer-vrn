<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_BACK' => 'Вернутся к списку правил',
        'CORSIK_DELIVERY_SERVICE_LABEL_PRICE' => "Цена (руб.)",
        'CORSIK_DELIVERY_SERVICE_PRICE_DESCRIPTION' => "Правило определения стоимости доставки по общей стоимости товаров",
        'CORSIK_DELIVERY_SERVICE_PRICE_LABEL_MIN' => "Минимальная стоимость:",
        'CORSIK_DELIVERY_SERVICE_PRICE_LABEL_MAX' => "Максимальная стоимость:",
        'CORSIK_DELIVERY_SERVICE_WEIGHT_DESCRIPTION' => "Правило определения стоимости доставки по весу заказа",
        'CORSIK_DELIVERY_SERVICE_WEIGHT_LABEL_MIN' => "Минимальный вес (грамм):",
        'CORSIK_DELIVERY_SERVICE_WEIGHT_LABEL_MAX' => "Максимальный вес (грамм):",
    ]);
}
