<?php
/**
 * Created: 20.03.2021, 19:23
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
    'NAME' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_COMPONENT_DESCRIPTION'),
    'SORT' => 100,
    'COMPLEX' => 'N',
    'PATH' => [
        'ID' => '34web',
        'CHILD' => [
            'ID' => 'BITRIX24',
            'NAME' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_CHILD_NAME')
        ]
    ]
);