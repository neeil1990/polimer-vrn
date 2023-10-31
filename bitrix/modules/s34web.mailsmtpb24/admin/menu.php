<?php
/**
 * Created: 19.03.2021, 15:14
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Config\Option;

//  Loads language messages for specified file in a lazy way
Loc::loadMessages(__FILE__);

// get module variables
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
$moduleName = str_replace('.', '_', $moduleID);
$moduleCode = strtoupper(str_replace('.', '_', $moduleID));

$moduleInstallDir = basename(pathinfo(pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'])['dirname']);

$activeModule = Option::get($moduleID, 'active_module','N');

$cssFile = '/'.$moduleInstallDir. '/css/' . $moduleID . '/menu.min.css';

$context = Bitrix\Main\Application::getInstance()->getContext();
$server = $context->getServer();
$docRoot = $server->getDocumentRoot();

// add css for menu icon
if(Bitrix\Main\IO\File::isFileExists($docRoot.$cssFile))
{
    // register CSS
    $arCssJsConfig = array(
        'mailSMTPB24Menu' => [
            'css' => $cssFile,
        ]
    );
    foreach ($arCssJsConfig as $ext => $arExt) {
        CJSCore::RegisterExt($ext, $arExt);
    }
    CJSCore::Init(['mailSMTPB24Menu']);
}


// set only if module Active
if($activeModule == 'Y') {
    global $APPLICATION;
    if ($APPLICATION->GetGroupRight($moduleID) >= 'R') {
        // add menu items on global service menu section
        $aMenu = [
            'parent_menu' => 'global_menu_services',
            'section' => $moduleID,
            'sort' => 1,
            'text' => Loc::getMessage($moduleCode . '_MENU_TEXT'),
            'title' => Loc::getMessage($moduleCode . '_MENU_TITLE'),
            'icon' => 'b24_smtp_mail_menu_icon',
            'page_icon' => 'smtp_mail_page_icon',
            'items_id' => 'menu_smtp_mail',
            'items' => [
                [
                    'text' => Loc::getMessage($moduleCode . '_SMTP_ACCOUNTS_TEXT'),
                    'title' => Loc::getMessage($moduleCode . '_SMTP_ACCOUNTS_TITLE'),
                    'sort' => 10,
                    'url' => '/bitrix/admin/' . $moduleName . '_accounts.php?lang='. LANGUAGE_ID,
                    'more_url' => [
                        '/bitrix/admin/' . $moduleName . '_accounts.php',
                        '/bitrix/admin/' . $moduleName . '_accounts_edit.php?lang=' . LANGUAGE_ID,
                        '/bitrix/admin/' . $moduleName . '_accounts_edit.php'
                    ]
                ],
                [
                    'text' => Loc::getMessage($moduleCode . '_LOGS_TEXT'),
                    'title' => Loc::getMessage($moduleCode . '_LOGS_TITLE'),
                    'sort' => 20,
                    'url' => '/bitrix/admin/' . $moduleName . '_logs.php?lang='. LANGUAGE_ID
                ]
            ]
        ];

        return $aMenu;
    }
}