<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_ERROR_PERSON_TYPE' => "Не выбран тип плательщика",
    ]);
}
