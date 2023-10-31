<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        "CORSIK_DELIVERY_SERVICE_SITE_TAB" => " [#ID#] #SITE#",
    ]);
}
