<?php
/**
 * Created: 20.03.2021, 20:22
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/**
 * Bitrix vars
 *
 * @global CMain $APPLICATION
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y') {
    $APPLICATION->includeComponent(
        'bitrix:mail.client.sidepanel',
        '',
        array(
            'COMPONENT_ARGUMENTS' => array('s34web:mail.smtp.b24.config', '', [])
        )
    );
} else {
    $APPLICATION->includeComponent('s34web:mail.smtp.b24.config', '', []);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');