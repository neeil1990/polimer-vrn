<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        'CORSIK_DELIVERY_SERVICE_POPUP_TITLE' => 'Карта доставки',
        'CORSIK_DELIVERY_SERVICE_OUT_DISABLES' => 'Доставка по данному адресу невозможна.',
        'CORSIK_DELIVERY_SERVICE_INPUT_ADDRESS_PLACEHOLDER' => 'Куда. Укажите город, улицу, дом',
        'CORSIK_DELIVERY_SERVICE_INPUT_ADDRESS_APPLY' => 'Выбрать этот адрес',
        'CORSIK_DELIVERY_SERVICE_BEFORE_SAVED_DELIVERY_ERROR' => 'Для дальнейшего оформления заказа вам необходимо  
                <a href="javascript:void(0);" onclick="window.animatedTo(\'DELIVERY\')">рассчитать стоимость доставки</a>'
    ]);
}
