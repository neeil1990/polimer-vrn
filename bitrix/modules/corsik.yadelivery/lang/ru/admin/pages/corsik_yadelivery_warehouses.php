<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_YANDEX_ZONE_MAP' => "Зона доставки",
        'CORSIK_DELIVERY_SERVICE_ADD_BTN_ZONE' => 'Добавить склад',
    ]);
}
