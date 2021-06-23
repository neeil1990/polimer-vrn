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

	$request = Main\Context::getCurrent()->getRequest();
	$iblockId = (int)$request->getPost('IBLOCK_ID');
	$sourcePath = $request->getPost('SOURCE_FIELD');
	$sourceParts = explode('.', $sourcePath);
	$query = trim($request->getPost('QUERY'));

	if (!Main\Application::isUtfMode())
	{
		$query = Main\Text\Encoding::convertEncoding($query, 'UTF-8', LANG_CHARSET);
	}

	if ($iblockId > 0 && Market\Data\TextString::getLength($query) >= 1 && count($sourceParts) === 2)
	{
		$sourceTypeName = $sourceParts[0];
		$sourceFieldName = $sourceParts[1];
		$context = Market\Export\Entity\Iblock\Provider::getContext($iblockId);
		$source = Market\Export\Entity\Manager::getSource($sourceTypeName);
		$sourceField = $source->getField($sourceFieldName, $context);

		if ($sourceField === null)
		{
			throw new Main\ObjectNotFoundException(Loc::getMessage('YANDEX_MARKET_FILTER_AUTOCOMPLETE_FIELD_NOT_FOUND'));
		}
		else if (empty($sourceField['AUTOCOMPLETE']))
		{
			throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_FILTER_AUTOCOMPLETE_FIELD_NOT_SUPPORT_AUTOCOMPLETE'));
		}
		else
		{
			$suggestList = $source->getFieldAutocomplete($sourceField, $query, $context);

			$response = [
				'status' => 'ok',
				'suggest' => (array)$suggestList
			];
		}
	}
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

echo Market\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';