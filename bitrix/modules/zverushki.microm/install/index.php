<?php
/**
 * Micro Marking
 *
 * @copyright 2001-2013 Zverushki
 */
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if (class_exists('zverushki_microm'))
	return;


class zverushki_microm extends CModule
{
	var $MODULE_ID = 'zverushki.microm';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_ICON = 'images/logo_schema.png';
	var $MODULE_SORT = 1;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = 'Y';
	var $PARTNER_NAME;
	var $PARTNER_URI;

	public function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace('\\', '/', __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));

		include $path.'/version.php';

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		// $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y';
		$this->MODULE_NAME = Loc::getMessage('MICROM_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('MICROM_MODULE_DESCRIPTION');
		$this->PARTNER_NAME = 'Zverushki';
        $this->PARTNER_URI = 'http://zverushki.ru';
	}

	function DoInstall()
	{
		global $DB, $APPLICATION;

		$this->installFiles();
		$this->installDB();
		$this->installEvents();

		/*$ar = Array(
		   "MESSAGE" => Loc::getMessage('MICROM_PUBLIC_NOTIFY'),
		   "MODULE_ID" => $this->MODULE_ID
		);
		$ID = \CAdminNotify::Add($ar);*/
	}

	function installDB()
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if (!$DB->query("SELECT 'x' FROM zverushki_microm", true)) {
			$this->errors = $DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/zverushki.microm/install/db/'.strtolower($DB->type).'/install.sql');
		}

		if ($this->errors !== false)
		{
			$APPLICATION->throwException(implode('', $this->errors));

			return false;
		}


		registerModule($this->MODULE_ID);

		return true;
	}

	function installEvents()
	{
		$eventManager = Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID);

		return true;
	}

	function installFiles()
	{
		copyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/zverushki.microm/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
		copyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/zverushki.microm/install/templates/.default', $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/.default', true, true);

		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;

		$this->uninstallDB(array('savedata' => \bitrix\main\Loader::IncludeModule("zverushki.microml") ? "Y" : "N"));
		$this->uninstallFiles();
		$this->uninstallEvents();
	}

	function uninstallDB($arParams = array())
	{

		Option::delete($this->MODULE_ID);
		unregisterModule($this->MODULE_ID);

		global $DB, $APPLICATION;

		$this->errors = false;

		if ($arParams["savedata"] != "Y")
			$this->errors = $DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/zverushki.microm/install/db/'.strtolower($DB->type).'/uninstall.sql');

		if ($this->errors !== false)
		{
			$APPLICATION->throwException(implode('', $this->errors));

			return false;
		}
		return true;
	}

	function uninstallEvents()
	{
		$eventManager = Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID);

		return true;
	}

	function uninstallFiles()
	{
		deleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/zverushki.microm/install/admin',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
		);

		return true;
	}

}