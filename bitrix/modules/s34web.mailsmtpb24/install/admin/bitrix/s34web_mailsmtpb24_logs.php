<?php
/**
 * Created: 17.04.2021, 17:00
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

if(file_exists($_SERVER["DOCUMENT_ROOT"] .
    '/bitrix/modules/s34web.mailsmtpb24/admin/s34web_mailsmtpb24_logs.php')) {
    require_once($_SERVER["DOCUMENT_ROOT"] .
        '/bitrix/modules/s34web.mailsmtpb24/admin/s34web_mailsmtpb24_logs.php');
}
