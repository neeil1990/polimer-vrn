<?php

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class yandex_market extends CModule
{
    var $MODULE_ID = 'yandex.market';
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $PARTNER_NAME;
	var $PARTNER_URI;

    function __construct()
    {
        $arModuleVersion = null;

        include __DIR__ . '/version.php';

        if (isset($arModuleVersion) && is_array($arModuleVersion))
        {
	        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
	        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

	    $this->MODULE_NAME = GetMessage('YANDEX_MARKET_MODULE_NAME');
	    $this->MODULE_DESCRIPTION = GetMessage('YANDEX_MARKET_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = GetMessage('YANDEX_MARKET_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('YANDEX_MARKET_PARTNER_URI');
    }

    function GetModuleRightList()
	{
		$arr = [
			'reference_id' => ['D', 'PT', 'PE', 'R', 'W'],
			'reference' => [
				'[D] ' . GetMessage('YANDEX_MARKET_RIGHTS_DENIED'),
				'[PT] ' . GetMessage('YANDEX_MARKET_RIGHTS_PROCESS_TRADING'),
				'[PE] ' . GetMessage('YANDEX_MARKET_RIGHTS_PROCESS_EXPORT'),
				'[R] ' . GetMessage('YANDEX_MARKET_RIGHTS_READ'),
				'[W] ' . GetMessage('YANDEX_MARKET_RIGHTS_WRITE')
			]
		];
		return $arr;
	}

    function DoInstall()
    {
        global $APPLICATION;

        $result = true;

        try
        {
	        $this->checkRequirements();

	        Main\ModuleManager::registerModule($this->MODULE_ID);

	        if (Main\Loader::includeModule($this->MODULE_ID))
	        {
		        $this->InstallDB();
		        $this->InstallEvents();
		        $this->InstallAgents();
		        $this->InstallFiles();
		    }
		    else
		    {
		        throw new Main\SystemException(GetMessage('YANDEX_MARKET_MODULE_NOT_REGISTERED'));
		    }
	    }
	    catch (\Exception $exception)
	    {
	        $result = false;
	        $APPLICATION->ThrowException($exception->getMessage());
	    }

	    return $result;
    }

    function DoUninstall()
    {
		global $APPLICATION, $step;

		$step = (int)$step;

		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage('YANDEX_MARKER_UNINSTALL'), __DIR__ . '/unstep1.php');
		}
		else if ($step === 2)
		{
			if (Main\Loader::includeModule($this->MODULE_ID))
			{
				$request = Main\Context::getCurrent()->getRequest();
				$isSaveData = $request->get('savedata') === 'Y';

				if (!$isSaveData)
				{
					$this->UnInstallDB();
				}

				$this->UnInstallEvents();
				$this->UnInstallAgents();
				$this->UnInstallFiles();
			}

			Main\ModuleManager::unRegisterModule($this->MODULE_ID);
		}
    }

    function InstallDB()
    {
		Market\Reference\Storage\Controller::createTable();
    }

    function UnInstallDB()
    {
        Market\Reference\Storage\Controller::dropTable();
    }

    function InstallEvents()
    {
		Market\Migration\Event::reset();
    }

    function UnInstallEvents()
    {
        Market\Reference\Event\Controller::deleteAll();
    }

    function InstallAgents()
    {
        Market\Reference\Agent\Controller::updateRegular();
    }

    function UnInstallAgents()
    {
		Market\Reference\Agent\Controller::deleteAll();
    }

    function InstallFiles()
    {
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/css', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/templates', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/templates/.default/components', true, true);
	    CopyDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. '/bitrix/themes', true, true);
        CopyDirFiles(__DIR__ . '/services', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/services/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/tools', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/yandex.market', true, true);
    }

    function UnInstallFiles()
    {
        DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
		DeleteDirFilesEx('bitrix/components/yandex.market');
		DeleteDirFilesEx('bitrix/css/yandex.market');
		DeleteDirFilesEx('bitrix/js/yandex.market');
        DeleteDirFilesEx('bitrix/images/yandex.market');
	    DeleteDirFilesEx('bitrix/services/yandex.market');
	    DeleteDirFilesEx('bitrix/tools/yandex.market');
		DeleteDirFilesEx('bitrix/templates/.default/components/bitrix/main.lookup.input/ym_userfield');
		DeleteDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. '/bitrix/themes');
		DeleteDirFilesEx('bitrix/themes/.default/icons/yandex.market');
    }

    function checkRequirements()
    {
        // require php version

		$requirePhp = '5.6.0';

        if (CheckVersion(PHP_VERSION, $requirePhp) === false)
        {
			throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_PHP', [ '#VERSION#' => $requirePhp ]));
        }

        // require simplexml extension

		if (!class_exists('\\SimpleXMLElement'))
		{
			throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_SIMPLEXML'));
		}

        // required modules

        $requireModules = [
			'main' => '15.5.0',
			'iblock' => '15.0.0'
		];

        if (class_exists('\\Bitrix\\Main\\ModuleManager'))
        {
			foreach ($requireModules as $moduleName => $moduleVersion)
			{
				$currentVersion = Main\ModuleManager::getVersion($moduleName);

				if ($currentVersion !== false && CheckVersion($currentVersion, $moduleVersion))
				{
					unset($requireModules[$moduleName]);
				}
			}
        }

        if (!empty($requireModules))
        {
            foreach ($requireModules as $moduleName => $moduleVersion)
            {
                throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_MODULE', [
                    '#MODULE#' => $moduleName,
                    '#VERSION#' => $moduleVersion
                ]));
            }
        }
    }
}