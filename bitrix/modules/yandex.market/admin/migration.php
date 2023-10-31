<?php


use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

$accessLevel = $APPLICATION->GetGroupRight('yandex.market');

if ($accessLevel < 'W')
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_MIGRATION_REQUIRE_MODULE')
	]);
}
else if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_MIGRATION_REQUIRE_MODULE')
	]);

	return;
}

$APPLICATION->SetTitle(Market\Config::getLang('ADMIN_MIGRATION_TITLE'));

$request = Main\Context::getCurrent()->getRequest();

if ($request->getPost('run') === 'Y' && check_bitrix_sessid())
{
	try
	{
		Market\Migration\Controller::reset();

		CAdminMessage::ShowMessage(array(
			'TYPE' => 'OK',
			'MESSAGE' => Market\Config::getLang('ADMIN_MIGRATION_READY')
		));
	}
	catch (Main\SystemException $exception)
	{
		CAdminMessage::ShowMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage()
		));
	}
}
else
{
	?>
	<form method="post">
		<?= bitrix_sessid_post(); ?>
		<button class="adm-btn" type="submit" name="run" value="Y"><?= Market\Config::getLang('ADMIN_MIGRATION_RUN'); ?></button>
	</form>
	<?
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';