<?php

use Yandex\Market;
use Bitrix\Main;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('require module yandex.market');
	}

	if (!Market\Ui\Access::isWriteAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();
	$httpRequestData = $httpRequest->getQueryList()->toArray();
	$response = new Market\Api\OAuth2\VerificationCode\Response($httpRequestData);
	$validateResult = $response->validate();

	if (!$validateResult->isSuccess())
	{
		$errorMessage = implode('<br />', $validateResult->getErrorMessages());
		throw new Main\SystemException($errorMessage);
	}

	CAdminMessage::ShowMessage([
		'TYPE' => 'OK',
		'MESSAGE' => 'Ok'
	]);

	?>
	<script>
		if (window.opener) {
			window.opener.postMessage({
				method: 'yaMarketAuth',
				result: true,
				code: '<?= htmlspecialcharsbx($response->getVerificationCode()); ?>'
			}, '*');

			window.close();
		}
	</script>
	<?
}
catch (Main\SystemException $exception)
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage()
	]);

	?>
	<script>
		window.opener && window.opener.postMessage({
			method: 'yaMarketAuth',
			result: false
		}, '*');
	</script>
	<?
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';