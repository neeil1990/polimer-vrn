<?php
/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SaleOrderAjax $component
 * @var string $templateFolder
 */

const STOP_STATISTICS = true;
const NO_KEEP_STATISTIC = 'Y';
const NO_AGENT_STATISTIC = 'Y';
const DisableEventsCheck = true;
const BX_SECURITY_SHOW_MESSAGE = true;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\PostDecodeFilter;

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new PostDecodeFilter());

$signer = new Signer();
try {
    $params = $signer->unsign($request->get('signedParamsString'), 'darneo.ozon.export.product.detail');
    $serialize = base64_decode($params);
    if (CheckSerializedData($serialize)) {
        $params = unserialize($serialize, ['allowed_classes' => false]);
    } else {
        throw new SystemException('Error Serialized');
    }
} catch (BadSignatureException $e) {
    die();
} catch (ArgumentTypeException $e) {
    die();
} catch (SystemException $e) {
    die();
}

switch ($request->get('action')) {
    case 'update':
        $params['ACTION'] = 'update';
        break;
    case 'delete':
        $params['ACTION'] = 'delete';
        break;
    default:
        exit();
}

$result = $APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.product.detail',
    '',
    $params
);

$APPLICATION->RestartBuffer();

echo Json::encode($result);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
