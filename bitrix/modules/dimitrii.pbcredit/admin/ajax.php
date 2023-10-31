<?php
/**
 * Created by PhpStorm.
 * User: Dimitrii
 * Date: 30.03.2018
 * Time: 23:48
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

use \Dimitrii\PBCredit\PBCOrder;

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$instance = Application::getInstance();
$context = $instance->getContext();
$request = $context->getRequest();

$lang = ($request->get('lang') !== null) ? trim($request->get('lang')) : "ru";
\Bitrix\Main\Context::getCurrent()->setLanguage($lang);

Loc::loadMessages(__FILE__);

$arResult = array("ERROR" => "");

if (!\Bitrix\Main\Loader::includeModule('sale'))
    $arResult["ERROR"] = "Error! Can't include module \"Sale\"";

if (!\Bitrix\Main\Loader::includeModule('dimitrii.pbcredit'))
    $arResult["ERROR"] = "Error! Can't include module \"dimitrii.pbcredit\"";

if ($arResult["ERROR"] === '' && check_bitrix_sessid()) {
    $action = ($request->get('ACTION') !== null) ? trim($request->get('ACTION')) : '';
    $order = ($request->get('ORDER') !== null) ? $request->get('ORDER') : false;


    switch ($action) {
        case "CREATE_ORDER":
            global $USER;
            try {
                $pBOrder = new PBCOrder($order);
                $pBOrder->createOrder();
            } catch (Exception $e) {
                $arResult["ERROR"] = "Error! Create order - error";
            }
            break;
        case "SET_ID":
            try {
                if ($order) {
                    $pBOrder = new PBCOrder($order);
                    $pBOrder->setId();

                } else {
                    $arResult["ERROR"] = "Error! Order is empty!";
                }
            } catch (Exception $e) {
                $arResult["ERROR"] = "Error! Set id order - error";
            }
            break;
        default:
            $arResult["ERROR"] = "Error! Wrong action!";
            break;
    }
} else {
    if (strlen($arResult["ERROR"]) <= 0)
        $arResult["ERROR"] = "Error! Access denied";
}

if (strlen($arResult["ERROR"]) > 0)
    $arResult["RESULT"] = "ERROR";
else
    $arResult["RESULT"] = "OK";

if (strtolower(SITE_CHARSET) != 'utf-8')
    $arResult = $APPLICATION->ConvertCharsetArray($arResult, SITE_CHARSET, 'utf-8');

header('Content-Type: application/json');
die(json_encode($arResult));