<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2016 Altair TK. (http://www.wsrubi.ru)
 */
	$MESS['WSRUBI_TAB_EXTRAMAIL_SETTINGSOUTSMTP'] 		= "Настройки";
	$MESS['WSRUBI_TAB_TITLE_EXTRAMAIL_SETTINGSOUTSMTP'] = "Настройки для отправки писем через smtp сервер";
	$MESS['MAIN_TAB_LOG'] 								= "Журнал";
	$MESS['MAIN_TAB_LOG'] 								= "Журнал";
	$MESS['wsrubismtp_include_on'] 						= "Файл модуля подключен";
	$MESS['wsrubismtp_include_off'] 					= "Файл модуля не подключен";
    $MESS['wsrubismtp_how_include'] 					= 'Для подключения модуля необходимо добавить строку <br/><b>include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");</b><br/> в файл <b>/bitrix/php_interface/init.php</b> или <b>/local/php_interface/init.php</b>, если файл отсутствует то его необходимо создать';

	$MESS['heading_smtp_type_profile'] 			        = "Типовые профили (пред установки)";
    $MESS['settings_smtp_type_profile'] 			    = "Типовые профили";
    $MESS['settings_smtp_type_profile_yandex'] 			= "SMTP Yandex";
    $MESS['settings_smtp_type_profile_google'] 			= "SMTP Google";
    $MESS['settings_smtp_type_profile_mail_ru'] 		= "SMTP Mail.ru";

	$MESS['heading_smtp_connection_settings'] 			= "Настройка соединения";
 	$MESS['heading_smtp_connection_settings_for'] 		= "Настройка соединения для - ";

    $MESS['heading_headers'] 			                = "Заголовки письма";
    $MESS['wsrubismtp_remove_headers'] 			        = "Удалить заголовки";

	$MESS['posting'] 									= "Не использовать для рассылки";
    $MESS['onlyposting'] 								= "Использовать только для рассылки";
	$MESS['check_settings'] 							= "Отправка тестового письма";
	$MESS['smtp_active'] 								= "Модуль активен";
    $MESS['settings_smtp_from'] 						= "От ( Если не указан то используется e-mail из настроек Главного модуля -> 'E-Mail администратора сайта (отправитель по умолчанию)', если и он не указан то test@test.ru )";
	$MESS['settings_smtp_host'] 						= "SMTP Хост ";
	$MESS['settings_smtp_port'] 						= "SMTP Порт  ( К примеру 25,465 ...)";
	$MESS['settings_smtp_type_auth'] 					= "Тип аунтификации";
	$MESS['settings_smtp_type_auth_smtp'] 				= "SMTP аунтификация";
	$MESS['settings_smtp_type_auth_login'] 				= "Login аунтификация(по умолчанию)";
    $MESS['settings_smtp_type_auth_login_info'] 		= "Многие SMTP требуют полного совпадения `SMTP имени пользователя` и адреса указанного в `От кого`. Проверяйте шаблоны писем ( Настройки->Настройки продуктов->Почтовые шаблоны )";
	$MESS['settings_smtp_type_auth_plain'] 				= "Plain аунтификация";
	$MESS['settings_smtp_type_auth_crammd5']			= "CRAM-MD5 аунтификация";
	$MESS['settings_smtp_login'] 						= "SMTP Имя пользователя";
	$MESS['settings_smtp_password'] 					= "SMTP Пароль пользователя";
	$MESS['settings_smtp_log'] 							= "Вести журнал";
	$MESS['settings_smtp_log_message'] 					= "Нет записей";
	$MESS['settings_smtp_test'] 						= "Проверка";
	$MESS['settings_smtp_connection_default'] 			= "Проверка не проводилась";
	$MESS['settings_smtp_connection_error'] 			= "Ошибка соединения";
	$MESS['settings_smtp_connection_success'] 			= "Соединение установлено";
	$MESS['settings_smtp_testing']			 			= "Проверить";
	$MESS['settings_smtp_testing_info']			 		= "Перед тестирование необходимо применить настройки";
	$MESS['settings_smtp_testing_email']			 	= "Почтовый ящик для теста";
	$MESS['settings_test_message_subject'] 				= "test SMTP";
	$MESS['settings_test_message_body'] 				= "test SMTP";
	$MESS['settings_smtp_log_clean'] 					= "Очистить журнал";
	$MESS['settings_smtp_type_encryption'] 				= "Тип шифрования";
	$MESS['settings_smtp_timeout'] 						= "Таймаут (в секундах)";
	$MESS['settings_smtp_timeout_error'] 				= "Ошибка ввода поля 'Таймаут'";
	$MESS['settings_smtp_type_encryption_no'] 			= "Без шифрования";
	$MESS['settings_smtp_type_encryption_ssl'] 			= "SSL";
	$MESS['settings_smtp_type_encryption_tls'] 			= "TLS";
	$MESS['settings_smtp_convert_to_utf8'] 				= "Конвертировать тело письма в utf-8(только для сайтов с кодировкой Windows-1251)";
	$MESS['heading_smtp_connection_settings_advanced']  = "Дополнительные настройки";
	$MESS['header_settings_smtp_advanced']	 		    = "Добавить дополнительный E-mail SMTP аккаунт";

	$MESS["MAIN_settings_APPLY"] 						= "Применить";
	$MESS["MAIN_settings_APPLY_TITLE"] 					= "Сохранить изменения и остаться в форме";
	$MESS['SAVE'] 										= "Сохранить";
	$MESS['ADD'] 										= "Добавить";
	$MESS['RESET']	 									= "Сбросить";
	$MESS['DELETE']	 									= "Удалить";
	$MESS['Timeout']	 								= "Таймаут(секунд)";
    $MESS['addrtovalidation']	 						= "Проверять формат адреса получателя";
    $MESS['save_email_error']	 						= "Сохранять письмо при ошибке";

	$MESS["MAIN_EVENTLOG_WRONG_TIMESTAMP_X_FROM"] = "Введите в фильтре правильную дату \"c\" записи в журнал.";
	$MESS["MAIN_EVENTLOG_WRONG_TIMESTAMP_X_TO"] = "Введите в фильтре правильную дату \"по\" записи в журнал.";
	$MESS["MAIN_EVENTLOG_ID"] = "ID";
	$MESS["MAIN_EVENTLOG_TIMESTAMP_X"] = "Время";
	$MESS["MAIN_EVENTLOG_SEVERITY"] = "Срочность";
	$MESS["MAIN_EVENTLOG_AUDIT_TYPE_ID"] = "Событие";
	$MESS["MAIN_EVENTLOG_MODULE_ID"] = "Источник";
	$MESS["MAIN_EVENTLOG_ITEM_ID"] = "Объект";
	$MESS["MAIN_EVENTLOG_REMOTE_ADDR"] = "IP";
	$MESS["MAIN_EVENTLOG_USER_AGENT"] = "User Agent";
	$MESS["MAIN_EVENTLOG_REQUEST_URI"] = "URL";
	$MESS["MAIN_EVENTLOG_SITE_ID"] = "Сайт";
	$MESS["MAIN_EVENTLOG_USER_ID"] = "Пользователь";
	$MESS["MAIN_EVENTLOG_DESCRIPTION"] = "Описание";
	$MESS["MAIN_EVENTLOG_GUEST_ID"] = "Гость";
	$MESS["MAIN_EVENTLOG_LIST_PAGE"] = "Записи";
	$MESS["MAIN_EVENTLOG_PAGE_TITLE"] = "Журнал событий";
	$MESS["MAIN_EVENTLOG_SEARCH"] = "Найти";
	$MESS["MAIN_EVENTLOG_USER_AUTHORIZE"] = "Успешный вход";
	$MESS["MAIN_EVENTLOG_USER_LOGIN"] = "Ошибки входа";
	$MESS["MAIN_EVENTLOG_USER_LOGINBYHASH_FAILED"] = "Ошибка входа при сохраненной авторизации";
	$MESS["MAIN_EVENTLOG_USER_LOGOUT"] = "Выход из системы";
	$MESS["MAIN_EVENTLOG_USER_REGISTER"] = "Регистрация нового пользователя";
	$MESS["MAIN_EVENTLOG_USER_REGISTER_FAIL"] = "Ошибка регистрации";
	$MESS["MAIN_EVENTLOG_USER_INFO"] = "Запрос на смену пароля пользователя";
	$MESS["MAIN_EVENTLOG_USER_PASSWORD_CHANGED"] = "Смена пароля пользователя";
	$MESS["MAIN_EVENTLOG_USER_DELETE"] = "Удаление пользователя";
	$MESS["MAIN_EVENTLOG_STOP_LIST"] = "стоп-лист";
	$MESS["MAIN_EVENTLOG_FORUM_MESSAGE"] = "Сообщение";
	$MESS["MAIN_EVENTLOG_FORUM_TOPIC"] = "Тема";
	$MESS["MAIN_EVENTLOG_GROUP"] = "Изменены группы пользователя";
	$MESS["MAIN_EVENTLOG_GROUP_POLICY"] = "Изменена политика безопасности группы";
	$MESS["MAIN_EVENTLOG_MODULE"] = "Изменен доступ группы к модулю";
	$MESS["MAIN_EVENTLOG_FILE"] = "Изменен доступ к файлу";
	$MESS["MAIN_EVENTLOG_TASK"] = "Изменен уровень доступа";
	$MESS["MAIN_EVENTLOG_IBLOCK"] = "Инфоблок";
	$MESS["MAIN_EVENTLOG_IBLOCK_DELETE"] = "Удален";
	$MESS["MAIN_ALL"] = "(все)";
	$MESS["MAIN_EVENTLOG_MP_MODULE_INSTALLED"] = "Решение Marketplace установленно";
	$MESS["MAIN_EVENTLOG_MP_MODULE_UNINSTALLED"] = "Решение Marketplace удалено";
	$MESS["MAIN_EVENTLOG_MP_MODULE_DELETED"] = "Решение Marketplace стерто";
	$MESS["MAIN_EVENTLOG_MP_MODULE_DOWNLOADED"] = "Решение Marketplace скачано";

    $MESS['WSRUBI_SUPPORT_TAB'] 		= "Поддержка";
    $MESS['WSRUBI_SUPPORT_TITLE'] 		= "Поддержка";
    $MESS['WSRUBI_ABOUT_DEV'] 		    = "Разработчик";
    $MESS['WSRUBI'] 		            = "Веб-студия RUBI";
    $MESS['WSRUBI_SERVICE_TITLE']       = "Наши услуги";
    $MESS['WSRUBI_SERVICE_LIST']        = "<ul><li>Специализированные разработки CRM/ERP/Порталы/Модули Битрикс;</li><li>разработка/доработка и обслуживание сайтов на \"Битрикс\";</li><li>реклама и продвижение в интернете;</li></ul>";
    $MESS['WSRUBI_SUPPORT']             = "Поддержка";
    $MESS['WSRUBI_SUPPORT_INFO']        = "Задать вопрос по работе модуля можно по электронной почте <a href='mailto:support@wsrubi.ru' >support@wsrubi.ru</a> или заполнив форму";
?>