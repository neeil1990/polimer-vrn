<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\InvalidPathException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Configuration;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

IncludeModuleLangFile(__FILE__);
Loc::loadMessages(__FILE__);

class darneo_ozon extends CModule
{
    public $MODULE_ID = 'darneo.ozon';
    public $MODULE_NAME;
    private $pathToModule;

    public function __construct()
    {
        include __DIR__ . '/version.php';

        $this->MODULE_NAME = GetMessage('DARNEO_OZON_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('DARNEO_OZON_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('DARNEO_OZON_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('DARNEO_OZON_PARTNER_URI');

        $this->MODULE_GROUP_RIGHTS = 'Y';

        if ($arModuleVersion !== null) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->pathToModule = $this->GetPath();
        $this->pathsToCssAndJs = [
            'js' => $this->pathToModule . '/install/js'
        ];
    }

    public function GetPath($notDocumentRoot = false)
    {
        if ($notDocumentRoot) {
            return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
        }

        return dirname(__DIR__);
    }

    public function DoInstall()
    {
        RegisterModule('darneo.ozon');
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallTasks();
        $this->InstallEvents();

        $arFields = [
            'MODULE_ID' => 'darneo.ozon',
            'TAG' => 'INSTALL_PUBLIC',
            'MESSAGE' => Loc::getMessage('DARNEO_OZON_UPDATER_INSTALL'),
        ];
        CAdminNotify::Add($arFields);
    }

    public function InstallDB()
    {
        $_SESSION['DARNEO_OZON_KEY_ID'] = 0;

        if (!Loader::includeModule('darneo.ozon')) {
            return false;
        }

        HelperSettings::setKeyIdCurrent();

        $entitiesDataClasses = (new Configuration())->get('entitiesDataClasses');
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            $entityDataClass::getEntity()->createDbTable();
        }

        CAgent::AddAgent('\Darneo\Ozon\Main\Agent\Update::check();', 'darneo.ozon', 'N', 86400);
        CAgent::AddAgent('\Darneo\Ozon\Import\Product\Connect::agentStart()', 'darneo.ozon', 'N', 3600);

        (new \Darneo\Ozon\Install\Settings())->setValue();

        \Darneo\Ozon\Cache::clean();

        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            $this->GetPath() . '/install/components',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components',
            true,
            true
        );

        foreach ($this->pathsToCssAndJs as $destinationDirectory => $placementInModuleDirectory) {
            if (\Bitrix\Main\IO\Directory::isDirectoryExists($placementInModuleDirectory)) {
                $pathTo = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/' . $destinationDirectory . '/' . 'darneo_ozon';
                CopyDirFiles($placementInModuleDirectory, $pathTo, true, true);
            } else {
                throw new InvalidPathException($placementInModuleDirectory);
            }
        }

        return true;
    }

    public function InstallEvents()
    {
        $event = EventManager::getInstance();
        $event->registerEventHandler(
            'main',
            'OnModuleUpdate',
            'darneo.ozon',
            \Darneo\Ozon\Main\Agent\Event::class,
            'onModuleUpdate'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallTasks();
        $this->UnInstallEvents();

        UnRegisterModule('darneo.ozon');

        $APPLICATION->IncludeAdminFile(
            'Удаление модуля',
            $this->GetPath() . '/install/unstep.php'
        );
    }

    public function UnInstallDB()
    {
        if (!Loader::includeModule('darneo.ozon')) {
            return false;
        }

        (new Darneo\Ozon\Configuration())->deleteTableAll();

        CAgent::RemoveModuleAgents('darneo.ozon');

        return true;
    }

    public function UnInstallFiles()
    {
        $directories = array_keys($this->pathsToCssAndJs);
        foreach ($directories as $directory) {
            $directoryForDelete = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/' . $directory . '/' . 'darneo_ozon';
            if (\Bitrix\Main\IO\Directory::isDirectoryExists($directoryForDelete)) {
                \Bitrix\Main\IO\Directory::deleteDirectory($directoryForDelete);
            }
        }

        DeleteDirFilesEx('/bitrix/wizards/darneo/darneo_ozon');

        return true;
    }

    public function UnInstallEvents(): void
    {
        $event = EventManager::getInstance();
        $event->unRegisterEventHandler(
            'main',
            'OnModuleUpdate',
            'darneo.ozon',
            \Darneo\Ozon\Main\Agent\Event::class,
            'onModuleUpdate'
        );
    }
}
