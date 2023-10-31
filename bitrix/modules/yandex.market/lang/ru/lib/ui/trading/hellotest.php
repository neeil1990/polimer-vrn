<?php

$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_PARAMETER_REQUIRED'] = 'Не указан обязательный параметр #PARAMETER#';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_SUCCESS'] = 'Запрос успешно обработан';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_HTTP_NOT_FOUND'] = 'Адрес не найден';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_HTTP_NOT_FOUND_DESCRIPTION'] = '
<h2 class="pos--top">Адрес не найден</h2>
<p>При обработке запросов используется urlrewrite, проверьте настройки веб-сервера.</p>
<p>Файл <strong>.htaccess</strong> должен содержать правило urlrewrite.php (<a href="https://dev.1c-bitrix.ru/api_help/main/general/urlrewrite.php">документация</a>, раздел &mdash;&nbsp;Подключение системы обработки адресов). Обработка запросов с помощью директивы ErrorDocument не поддерживается, в таком случае тело запроса не передается.</p>
<div class="yamarket-code">&lt;IfModule mod_rewrite.c&gt;
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
    RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
&lt;/IfModule&gt;</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_URLREWRITE_RULE_MISSING'] = 'Отсутствует правило urlrewrite';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_URLREWRITE_RULE_MISSING_DESCRIPTION'] = '
<h2 class="pos--top">Отсутствует правило urlrewrite</h2>
<p>При обработке запросов используется urlrewrite, проверьте наличие правила скрипта #SCRIPT_PATH#.</p>
<p>Повторно выполнить добавление правила можно, нажав &laquo;Отключить&raquo;  в форме настройки обработки заказов и &laquo;Включить&raquo; после перезагрузки страницы.</p>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_HTTP_REDIRECT'] = 'Выполнен редирект';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_HTTP_REDIRECT_DESCRIPTION'] = '
<h2 class="pos--top">Выполнен редирект</h2>
<p>
#FROM# &mdash;&nbsp;адрес запроса<br />
#TO# &mdash;&nbsp;адрес перенаправления.
</p>
<p>Исключите пользовательские редиректы внутри директории bitrix.</p>
<h3>.htaccess</h3>
<p>Добавьте исключение <strong>RewriteCond %{REQUEST_URI} !^/bitrix/</strong> перед пользовательскими правилами <strong>RewriteRule</strong>. Обратите внимание, нельзя добавлять исключение перед <strong>RewriteRule ^(.*)$ /bitrix/urlrewrite.php</strong>. Пример:</p>
<div class="yamarket-code">&lt;IfModule mod_rewrite.c&gt;
  Options +FollowSymLinks
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_URI} !\..{1,10}$
  RewriteCond %{REQUEST_URI} !(.*)/$
  <strong>RewriteCond %{REQUEST_URI} !^/bitrix/</strong>
  RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1/ [L,R=301]

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-l
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
  RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
  RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]
&lt;/IfModule&gt;
</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_TOKEN_MISSING'] = 'Токен авторизации утерян';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_TOKEN_MISSING_DESCRIPTION'] = '
<h2 class="pos--top">Токен авторизации утерян</h2>
<p>Используя заголовок Authorization HTTP запроса передаются данные авторизации, затем осуществляется попытка их определить, используя переменную сервера REMOTE_USER (или REDIRECT_REMOTE_USER, или HTTP_AUTHORIZATION).</p>
<h3>Apache</h3>
<p>Файл <strong>.htaccess</strong> должен содержать правило <strong>RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]</strong>. Пример:</p>
<div class="yamarket-code">&lt;IfModule mod_rewrite.c&gt;
  Options +FollowSymLinks
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-l
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
  RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
  <strong>RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]</strong>
&lt;/IfModule&gt;
</div>
<h3>Nginx или CGI/FastCGI</h3>
<p>Вам необходимо обратиться к хостинг-провайдеру для настройки передачи заголовков авторизации.</p>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_BODY_MISSING'] = 'Содержимое запроса утеряно';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_BODY_MISSING_DESCRIPTION'] = '
<h2 class="pos--top">Содержимое запроса утеряно</h2>
<p>При обработке запросов используется urlrewrite. Обработка запросов с помощью директивы ErrorDocument не поддерживается, в таком случае тело запроса не передается.</p>
<h3>Apache</h3>
<p>Файл <strong>.htaccess</strong> должен содержать правило urlrewrite.php (<a href="https://dev.1c-bitrix.ru/api_help/main/general/urlrewrite.php">документация</a>, раздел &mdash;&nbsp;Подключение системы обработки адресов).</p>
<div class="yamarket-code">&lt;IfModule mod_rewrite.c&gt;
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
    RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
&lt;/IfModule&gt;</div>
<h3>Nginx или CGI/FastCGI</h3>
<p>Вам необходимо обратиться к хостинг-провайдеру для настройки обработки запросов urlrewrite без использования директив обработки ошибок.</p>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_INTERNAL_ERROR'] = 'Внутренняя ошибка';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_INTERNAL_ERROR_DESCRIPTION'] = '
<h2 class="pos--top">Внутренняя ошибка</h2>
<p>При обработке запросов произошла внутренняя ошибка:</p>
<div class="yamarket-code">#RESPONSE#</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_SOCKET_CONNECT'] = 'Ошибка подключения';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_SOCKET_CONNECT_DESCRIPTION'] = '
<h2 class="pos--top">Ошибка подключения</h2>
<p>Проверьте правильность доменного имени и наличие https-сертификата.</p>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_CLIENT_ERROR'] = 'Ошибка запроса';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_CLIENT_ERROR_DESCRIPTION'] = '
<h2 class="pos--top">Ошибка запроса</h2>
<p>При выполнение запроса получена ошибка:</p>
<div class="yamarket-code">#ERROR#</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_UNKNOWN'] = 'Неизвестная ошибка';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_UNKNOWN_DESCRIPTION'] = '
<h2 class="pos--top">Неизвестная ошибка</h2>
<p>При выполнение запроса получена ошибка:</p>
<div>Cтатус ответа &mdash;&nbsp;#STATUS#</div>
<div class="yamarket-code">#RESPONSE#</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_LOCAL_REDIRECT'] = 'Выполнен редирект';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_LOCAL_REDIRECT_DESCRIPTION'] = '
<h2 class="pos--top">Выполнен редирект</h2>
<p>В файле <strong>#FILE#</strong> на строке <strong>#LINE#</strong> выполняется редирект на адрес <strong>#URL#</strong>. Обратитесь к разработчикам сайта, чтобы исключить редиректы внутри директории <strong>/bitrix/</strong>.</p>
<p>Стек вызова:</p>
<div class="yamarket-code">#TRACE#</div>
';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_MODULE_REDIRECT'] = 'Выполнен редирект';
$MESS['YANDEX_MARKET_UI_TRADING_HELLO_TEST_ERROR_MODULE_REDIRECT_DESCRIPTION'] = '
<h2 class="pos--top">Выполнен редирект</h2>
<p>Модуль <strong>#MODULE#</strong> выполняет редирект на адрес <strong>#URL#</strong>. Обратитесь к разработчикам модуля, чтобы исключить редиректы внутри директории <strong>/bitrix/</strong>.</p>
<p>Стек вызова:</p>
<div class="yamarket-code">#TRACE#</div>
';