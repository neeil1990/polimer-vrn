<?php
/**
 * Created: 25.03.2021, 15:49
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

$MESS['S34WEB_MAILSMTPB24_ACCOUNT_EDIT_ACCESS_DENIED'] = 'Доступ запрещен.';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ADMIN_TITLE'] = 'SMTP-аккаунт # #ID#';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_ERROR_TITLE'] = 'Ошибка работы модуля';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_ERROR_DEMO_EXPIRED_TEXT'] = 'Срок работы демо-режима модуля "Отправка почты через внешний SMTP (Коробка Битрикс24, Интернет магазин + СRM)" истек!';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_ERROR_NOT_FOUND_TEXT'] = 'Модуль "Отправка почты через внешний SMTP (Коробка Битрикс24, Интернет магазин + СRM)" не найден!';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_DEMO_TITLE'] = 'Ограниченный режим работы модуля "Отправка почты через внешний SMTP (Коробка Битрикс24, Интернет магазин + СRM)"';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_DEMO_TEXT'] = 'Модуль работает в демо-режиме! Вы можете купить версию без ограничений!';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_ACTIVE_ERROR_TITLE'] = 'Ошибка работы модуля';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_ACTIVE_ERROR_TEXT'] = 'Не включена работа в '.
    'параметрах настройки модуля! <a href="/bitrix/admin/settings.php?mid=s34web.mailsmtpb24&mid_menu=1&lang='.
    LANGUAGE_ID.'">Включить</a>?';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_CLASS_SMTP_ERROR_TITLE'] = 'Ошибка работы модуля';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MODULE_CLASS_SMTP_ERROR_TEXT'] = 'Не найден класс s34web\mailSMTPB24\smtpAccountsTable. Проверьте работу модуля "Отправка почты через внешний SMTP (Коробка Битрикс24, Интернет магазин + СRM)"!';

$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_NEW_ADMIN_TITLE'] = 'Добавление SMTP-аккаунта';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_TAB1'] = 'SMTP-аккаунт';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_TAB1_TITLE'] = 'Параметры SMTP-аккаунта';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_ACTIVE'] = 'Активен:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_NAME'] = 'Имя отправителя:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_EMAIL'] = 'Email отправителя:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_SERVER'] = 'Адрес почтового сервера:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_PORT'] = 'Порт почтового сервера:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_SECURE'] = 'Защита соединения:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_LOGIN'] = 'Логин:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_PASSWORD'] = 'Пароль:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_ADD_BUTTON'] = 'Добавить';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_ADD_BUTTON_TITLE'] = 'Добавить новый SMTP-аккаунт';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_DELETE_BUTTON'] = 'Удалить';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_DELETE_BUTTON_TITLE'] = 'Удалить текущий SMTP-аккаунт';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_DELETE_RECORD_CONF'] = 'Вы уверены что хотите удалить данный SMTP-аккаунт?';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_SAVING'] = 'Ошибка сохранения';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_EMAIL_EMPTY'] = 'Не указан Email отправителя';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_EMAIL_BAD'] = 'Недопустимый email отправителя';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_SERVER_EMPTY'] = 'Введите адрес почтового сервера';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_SERVER_BAD'] = 'Недопустимый адрес почтового сервера';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_PORT_EMPTY'] = 'Введите порт почтового сервера';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_PORT_BAD'] = 'Недопустимый порт';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_LOGIN_EMPTY'] = 'Введите логин';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_PASSWORD_EMPTY'] = 'Введите пароль';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_PASSWORD_BAD_CARET'] = 'Пароль не может начинаться с символа ^';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_PASSWORD_BAD_NULL'] = 'Пароль не должен содержать нулевых байтов';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_DOUBLE_EMAIL'] = 'Аккаунт с таким Email отправителя уже существует!';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_ACCOUNTS_LIST'] = 'Список SMTP-аккаунтов';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_MENU_ACCOUNTS_LIST_TITLE'] = 'Перейти в список SMTP-аккаунтов';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_AUTH'] = 'Тип авторизации:';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_AUTH_NO'] = 'Без авторизации';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_AUTH_CUSTOM'] = 'Ввести логин и пароль';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELD_SECURE_NO'] = 'Нет';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_FIELDS_HINT'] = 'При сохранении выполняется проверка авторизации на почтовом сервере!'.
    ' Если в настройках модуля включена опция "Выполнять отправку письма при проверке соединения", то дополнительно проверяется отправка письма!';
$MESS['S34WEB_MAILSMTPB24_ACCOUNTS_EDIT_ERROR_CLASS_MAILSENDER'] = 'Ошибка работы модуля. Не найден класс mailSender!';
