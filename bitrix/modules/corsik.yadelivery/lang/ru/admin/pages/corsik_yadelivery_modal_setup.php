<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_BALLOON_PRESET_NODE' => "Посмотреть ключи меток на сайте <a href='https://yandex.ru/dev/maps/jsapi/doc/2.1/ref/reference/option.presetStorage.html' target='_blank'>Яндекс Карт</a>",
        'CORSIK_DELIVERY_SERVICE_BALLOON_PRESET' => 'Настройка внешнего вида меток балунов',
        'CORSIK_DELIVERY_SERVICE_POINT_START_PRESET' => 'Внешний вид метки начала расчета',
        'CORSIK_DELIVERY_SERVICE_POINT_STOP_PRESET' => 'Внешний вид метки окончания расчета',
        'CORSIK_DELIVERY_SERVICE_WAREHOUSES_PRESET' => 'Внешний вид меток складов',
        "CORSIK_DELIVERY_SERVICE_DISPLAY_MODE_MODAL" => "Режим отображения окна с картой",
        "CORSIK_DELIVERY_SERVICE_DISPLAY_LIGHT_MODE" => "Легкий",
        "CORSIK_DELIVERY_SERVICE_DISPLAY_FULL_MODE" => "Полный",
        'CORSIK_DELIVERY_SERVICE_SHOW_WAREHOUSES' => 'Показывать метки складов на карте',
        'CORSIK_DELIVERY_SERVICE_SHOW_WAREHOUSES_TITLE' => 'Показывать названия складов',
        "CORSIK_DELIVERY_SERVICE_SHOW_OTHER_SETTINGS" => "Другие настройки",
        'CORSIK_DELIVERY_SERVICE_SHOW_ALERT_CALCULATE' => "<span data-hint='При загрузке страницы оформления заказа показывать Alert <<Для расчёта стоимости доставки выберите адрес на карте>>'></span> Выводить уведомление о необходимости рассчитать стоимость доставки",
        "CORSIK_DELIVERY_SERVICE_SHOW_MODAL_HEADER" => "<span data-hint='В данном блоке отображается окно поиск и выбора адреса (блок отображается только полном режиме)'></span> Показывать верхний блок на карте",
        "CORSIK_DELIVERY_SERVICE_SHOW_MODAL_FOOTER" => "<span data-hint='В данном блоке отображается стоимость доставки и расстояние до адреса (блок отображается только полном режиме)'></span> Показывать нижний блок на карте",
        "CORSIK_DELIVERY_SERVICE_SHOW_ROUTE_CALCULATE" => "Показывать расстояние до адреса доставки",
        "CORSIK_DELIVERY_SERVICE_SHOW_CALCULATE_PRICE" => "Показывать стоимость доставки",
    ]);
}
