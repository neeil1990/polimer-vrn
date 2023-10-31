<?php
/**
 * Created: 25.03.2021, 10:39
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

if(file_exists($_SERVER["DOCUMENT_ROOT"] .
    '/local/modules/s34web.mailsmtpb24/admin/s34web_mailsmtpb24_accounts.php')) {
    require_once($_SERVER["DOCUMENT_ROOT"] .
        '/local/modules/s34web.mailsmtpb24/admin/s34web_mailsmtpb24_accounts.php');
}
