<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Оставьте заявку на бесплатный подбор оборудования");
$APPLICATION->SetPageProperty("title", "Заявка на бесплатную консультация по подбору оборудования");
$APPLICATION->SetTitle("Бесплатная консультация по подбору оборудования");
?>


<?$APPLICATION->IncludeComponent("nbrains:main.feedback", "free-calc", Array(
    "COMPONENT_TEMPLATE" => ".default",
    "EMAIL_TO" => "sale@polimer-vrn",	// E-mail, на который будет отправлено письмо
    "EVENT_MESSAGE_ID" => array(	// Почтовые шаблоны для отправки письма
        0 => "88",
    ),
    "IBLOCK_ID" => "13",	// Код информационного блока
    "IBLOCK_TYPE" => "feedback",	// Тип информационного блока (используется только для проверки)
    "OK_TEXT" => "Спасибо, ваше сообщение принято.",	// Сообщение, выводимое пользователю после отправки
    "PROPERTY_CODE" => array(	// Поля формы
        0 => "FIO",
        1 => "EMAIL",
        2 => "PHONE",
        3 => "TIME_AFTER",
        4 => "DESC",
        5 => "RULE",
        6 => "FILE",
    ),
    "REQUIRED_FIELDS" => "",
    "USE_CAPTCHA" => "Y",	// Использовать защиту от автоматических сообщений (CAPTCHA) для неавторизованных пользователей
),
    false
);?>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>