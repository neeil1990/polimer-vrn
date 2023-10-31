<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/lang/ru/handbook.php';

if (isset($HANDBOOK) && is_array($HANDBOOK))
{
	$MESS = array_merge($HANDBOOK, [
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP" => "Общие настройки подсказок",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP_DESCRIPTION" => "Обязательно перейдите на страницы настройки подсказок <br> <a onclick='tabControl.SelectTab(\"#YANDEX#\")' class='corsik_setup_link'>Яндекс</a> и <a onclick='tabControl.SelectTab(\"#DADATA#\")' class='corsik_setup_link'>Dadata</a>",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SHOW_INPUT_ADDRESS" => "Отобразить дополнительное поле ввода адреса в доставке",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_TYPE_PAYER" => "Свойства заказа: #TYPE# ( #SITE# )",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_LOCATION_OPTIONS_ENABLE" => "<span data-hint='При включенной опции, модуль подсказок будет автоматически проставлять местоположение.'></span>Проставлять местоположение автоматически",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_OPTIONS_AFTER_LOCATION" => "Поле используемое для расчетов доставки",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_ONLY" => "<span data-hint='Выбранные поля будут очищатся если пользователь не выбрал предлагаемый вариант от DaData (данная опция очень удобна для поля \"Адрес доставки\")'></span>Запретить ручной ввод в выбранных свойствах",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA_INFO" => "<b>Расчет стоимости доставки</b> работает только с включенным подсказками DaData",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA" => "Включить подсказки от DaData",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_API_KEY_DADATA" => "API ключ DaData",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_API_KEY_DADATA_INFO" => "Для работы с подсказками DaData, вам необходимо зарегистрировать на сайте <a href='https://dadata.ru/profile/#info' target='_blank'>DaData.ru</a>",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_LOG_PATH" => "Название файла логов: ",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_RESTRICTION_ADDRESS" => "Ограничение области поиска адреса",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_RESTRICTION_ADDRESS_INFO" => "Поддержка ограничения области подсказок работает только для сервиса DaData. Ограничение подсказок Яндекса технически не возможна <a href='https://yandex.ru/blog/mapsapi/perestala-rabotat-svyazka-boundedby-strictbounds#61faf17ddfca23003f6c072b' target='_blank'>Официальный ответ службы поддержки Яндекс.Карт</a>",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_COUNT_ROW" => "Количество строк с вариантами подсказок",
		'CORSIK_DELIVERY_SERVICE_SUGGESTIONS_TYPE_PROMPTS' => "Выберите тип подсказок для определения адреса",
		'CORSIK_DELIVERY_SERVICE_SUGGESTIONS_YANDEX_ADDRESS' => "Свойство для определения подсказок",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION" => "<span data-hint='«Подсказки» могут возвращать адрес в административном либо в муниципальном делении'></span>Тип территориального деления",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION_ADMINISTRATIVE" => "Административное",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION_MUNICIPAL" => "Муниципальное",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_YANDEX_TITLE" => "Подсказки Яндекс",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA_TITLE" => "Подсказки Дадата",
		"CORSIK_DELIVERY_SERVICE_SUGGESTIONS_PAYER_TITLE" => "Тип плательщика: #NAME#",
	]);
}
