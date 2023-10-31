<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_ADD_BTN_ZONE' => 'Добавить зону доставки',
        'CORSIK_DELIVERY_SERVICE_UPDATE_BITRIX' => '<b>Для правильной работы необходимо обновить версию битрикса!</b>',
    ]);
}
