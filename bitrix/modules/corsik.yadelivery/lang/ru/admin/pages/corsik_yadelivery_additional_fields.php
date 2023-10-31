<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK)) {
    $MESS = array_merge($HANDBOOK, [
        "CORSIK_DELIVERY_SERVICE_SITE_TAB" => " [#ID#] #SITE#",
        'CORSIK_DELIVERY_SERVICE_VISUAL_COMMON_SETUP_TAB' => 'Общие настройки',
        'CORSIK_DELIVERY_SERVICE_ADDITIONAL_FIELDS_TAB' => 'Дополнительные поля',
        "CORSIK_DELIVERY_SERVICE_SHOW_INPUT_ADDRESS" => "<span data-hint='Данное поле всегда будет первым в списке свойств'></span> Отобразить дополнительное поле для ввода для \"Адреса доставки\"",
        "CORSIK_DELIVERY_SERVICE_SHOW_ADDITIONAL_FIELDS" => "<span data-hint='Выбранные свойства появятся в \"Службе доставки\"'></span> Отобразить дополнительные свойства заказа в \"Службе доставки\"",
        "CORSIK_DELIVERY_SERVICE_HIDDEN_ADDITIONAL_FIELDS" => "<span data-hint='Например, данные поля будут скрыты в блоке \"Покупатель\"'></span> Скрыть дополнительные свойства в других блоках заказа",
    ]);
}
