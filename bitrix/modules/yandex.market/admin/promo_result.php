<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$isPopup = (isset($_REQUEST['popup']) && $_REQUEST['popup'] === 'Y');

if ($isPopup)
{
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
}
else
{
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
    \CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_PROMO_LIST_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_PROMO_LIST_ACCESS_DENIED')
	]);
}
else
{
    Market\Metrika::load();

    $APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', array(
        'GRID_ID' => 'YANDEX_MARKET_ADMIN_PROMO_RESULT',
        'PROVIDER_TYPE' => 'Data',
        'DATA_CLASS_NAME' => Market\Export\Run\Storage\PromoTable::getClassName(),
        'USE_FILTER' => 'Y',
        'TITLE' => Market\Config::getLang('ADMIN_PROMO_RESULT_TITLE'),
        'NAV_TITLE' => Market\Config::getLang('ADMIN_PROMO_RESULT_NAV_TITLE'),
        'LIST_FIELDS' => array(
            'SETUP',
            'ELEMENT_ID',
            'STATUS',
            'LOG',
            'TIMESTAMP_X'
        ),
        'FILTER_FIELDS' => array(
            'SETUP',
            'ELEMENT_ID',
            'STATUS',
            'TIMESTAMP_X',
        ),
        'PRIMARY' => [
            'SETUP_ID',
            'ELEMENT_ID',
        ],
        'ROW_ACTIONS' => array(
            'LOG' => array(
                'URL' => '/bitrix/admin/yamarket_log.php?lang=' . LANGUAGE_ID . '&find_promo_id=#ELEMENT_ID#&find_setup=#SETUP_ID#&set_filter=Y&apply_filter=Y&popup=Y',
                'TEXT' => Market\Config::getLang('ADMIN_PROMO_RESULT_ROW_ACTION_LOG'),
                'WINDOW' => 'Y'
            ),
            'XML_CONTENT' => array(
                'URL' => '/bitrix/admin/yamarket_xml_element.php?lang=' . LANGUAGE_ID . '&type=promo&id=#ELEMENT_ID#&setup=#SETUP_ID#&popup=Y',
                'TEXT' => Market\Config::getLang('ADMIN_PROMO_RESULT_ROW_XML_CONTENT'),
                'WINDOW' => 'Y'
            )
        )
    ));
}

if ($isPopup)
{
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else
{
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}