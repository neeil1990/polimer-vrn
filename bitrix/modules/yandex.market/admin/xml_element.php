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
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_XML_ELEMENT_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_XML_ELEMENT_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$APPLICATION->SetAdditionalCSS('/bitrix/css/yandex.market/base.css');

	try
	{
		$request = Main\Context::getCurrent()->getRequest();

		$stepName = trim($request->get('type'));
		$setupId = (int)$request->get('setup');
		$elementId = trim($request->get('id'));

		$setup = Market\Export\Setup\Model::loadById($setupId);
		$processor = new Market\Export\Run\Processor($setup);
		$step = $processor->getStep($stepName);

		$existRows = $step->loadExistDataStorage([
			'=SETUP_ID' => $setupId,
			'=ELEMENT_ID' => $elementId,
		]);
		$existRow = reset($existRows);

		if ($existRow === false)
		{
			throw new Main\ObjectNotFoundException(Market\Config::getLang('ADMIN_XML_RESULT_NOT_STORED'));
		}

		if ((int)$existRow['STATUS'] !== Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
		{
			throw new Main\ObjectNotFoundException(Market\Config::getLang('ADMIN_XML_RESULT_NOT_WRITTEN'));
		}

		$primary = (string)(
			$step->useTagPrimary()
				? $existRow['PRIMARY']
				: $existRow['ELEMENT_ID']
		);

		if ($primary === '')
		{
			throw new Main\ObjectNotFoundException(Market\Config::getLang('ADMIN_XML_RESULT_WITHOUT_PRIMARY'));
		}

		$tag = $step->getTag();
		$tagName = $tag->getName();
		$writer = $processor->getWriter();
		$contents = $writer->searchTag($tagName, $primary);

		if ($contents !== null)
		{
			$contents = Market\Utils::prettyPrintXml($contents);

			echo '<pre class="b-code">';
			echo htmlspecialcharsbx($contents);
			echo '</pre>';
		}
		else
		{
			throw new Main\ObjectNotFoundException(Market\Config::getLang('ADMIN_XML_ELEMENT_NOT_FOUND'));
		}
	}
	catch (Main\SystemException $exception)
	{
		\CAdminMessage::ShowMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => $exception->getMessage()
        ]);
	}
}

if ($isPopup)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}