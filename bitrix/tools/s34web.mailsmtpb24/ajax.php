<?php
/**
 * Created: 22.10.2021, 14:35
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

$flagInclude = false;
if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/modules/s34web.mailsmtpb24/tools/ajax.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/local/modules/s34web.mailsmtpb24/tools/ajax.php');
    $flagInclude = true;
}
if(!$flagInclude && file_exists($_SERVER['DOCUMENT_ROOT'] .
        '/bitrix/modules/s34web.mailsmtpb24/tools/ajax.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/s34web.mailsmtpb24/tools/ajax.php');
}
