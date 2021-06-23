<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

define('BX_SESSION_ID_CHANGE', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$response = null;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_MODULE_NOT_INSTALLED'));
	}

	if (!Market\Ui\Access::isProcessExportAllowed())
	{
		throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_ACCESS_DENIED'));
	}

	session_write_close(); // release sessions

	$component = new CBitrixComponent();
	$component->arParams = [
		'MODEL_CLASS_NAME' => Market\Export\Setup\Model::getClassName()
	];

	$provider = new Market\Component\Setup\EditForm($component);

	$request = Main\Context::getCurrent()->getRequest();
	$baseName = $request->getPost('baseName') ?: 'IBLOCK_LINK';
	$iblockLinkList = (array)$request->getPost($baseName);

	if (!Main\Application::isUtfMode())
	{
		$iblockLinkList = Main\Text\Encoding::convertEncodingArray($iblockLinkList, 'UTF-8', LANG_CHARSET);
	}

	foreach ($iblockLinkList as &$iblockLink)
	{
		if (isset($iblockLink['ID']))
		{
			unset($iblockLink['ID']);
		}
	}
	unset($iblockLink);

	switch ($baseName)
	{
		case 'PROMO_GIFT':
			$setupFields = [
				'EXPORT_SERVICE' => Market\Export\Xml\Format\Manager::EXPORT_SERVICE_YANDEX_MARKET,
				'EXPORT_FORMAT' => Market\Export\Xml\Format\Manager::EXPORT_FORMAT_VENDOR_MODEL,
				'PROMO' => [
					[
						'ACTIVE' => '1',
						'PROMO_TYPE' => Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE,
						'PROMO_GIFT' => $iblockLinkList,
					]
				]
			];

			$response = $provider->ajaxActionFilterCount($setupFields, $baseName, Market\Export\Run\Manager::STEP_PROMO_GIFT);
		break;

		default:
			$setupFields = [
				'EXPORT_SERVICE' => Market\Export\Xml\Format\Manager::EXPORT_SERVICE_YANDEX_MARKET,
				'EXPORT_FORMAT' => Market\Export\Xml\Format\Manager::EXPORT_FORMAT_VENDOR_MODEL,
				'IBLOCK_LINK' => $iblockLinkList
			];

			$response = $provider->ajaxActionFilterCount($setupFields, $baseName);
		break;
	}
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

$APPLICATION->RestartBuffer();
echo Market\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);
die();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';