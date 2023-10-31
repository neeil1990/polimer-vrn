<?php

use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);



class dimitrii_pbcredit extends CModule
{


    const MODULE_ID = "dimitrii.pbcredit";
    var $MODULE_ID = "dimitrii.pbcredit";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    public function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . '/version.php';

        $this->exclusionAdminFiles=array(
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php'
        );

        $this->MODULE_ID = 'dimitrii.pbcredit';
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('DIMITRII_PBCREDIT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('DIMITRII_PBCREDIT_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('DIMITRII_PBCREDIT_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('DIMITRII_PBCREDIT_PARTNER_URI');

        $this->MODULE_GROUP_RIGHTS = 'N';
    }


    public function DoInstall()
    {
        global $APPLICATION;
        if ($this->isVersionD7()) {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->InstallFiles();
        } else {
            $APPLICATION->ThrowException(Loc::getMessage("DIMITRII_PBCREDIT_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("DIMITRII_PBCREDIT_INSTALL_TITLE"), $this->GetPath() . "/install/step.php");

    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function GetPath($notDocumentRoot = false)
    {
        if ($notDocumentRoot)
            return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '17.05.00');
    }

    function InstallFiles($arParams = array())
    {
        $pathComponents = $this->GetPath() . "/install/components";
        $pathPayment = $this->GetPath() . "/install/payment";


        if (\Bitrix\Main\IO\Directory::isDirectoryExists($pathComponents) ) {

            CopyDirFiles($pathComponents, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
            //CopyDirFiles($pathPayment, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/sale_payment", true, true);

        } else
            throw new \Bitrix\Main\IO\InvalidPathException($pathComponents);


        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/payment/dimitrii.pbcredit'))
        {
            CopyDirFiles($this->GetPath() . "/install/payment/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/sale_payment", true, true);

            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/dimitrii.pbcredit/payment.php',
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/payment/dimitrii.pbcredit/payment.php");?'.'>');

            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/dimitrii.pbcredit/.description.php',
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/payment/dimitrii.pbcredit/.description.php");?'.'>');

            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/dimitrii.pbcredit/ru/pochtabank.php',
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/payment/dimitrii.pbcredit/ru/pochtabank.php");?'.'>');

        } else
            throw new \Bitrix\Main\IO\InvalidPathException($this->GetPath() . "/install/");


        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
        {
            CopyDirFiles($this->GetPath() . "/install/admin/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin"); //???? ???? ????? ??? ???????????
            if ($dir = opendir($path))
            {
                while (false !== $item = readdir($dir))
                {
                    if (in_array($item,$this->exclusionAdminFiles))
                        continue;
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item,
                        '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/admin/'.$item.'");?'.'>');
                }
                closedir($dir);
            }
        } else
            throw new \Bitrix\Main\IO\InvalidPathException($this->GetPath() . "/install/");


        return true;

    }

    function UnInstallFiles()
    {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/dimitrii/pboneclickcredit');
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/dimitrii.pbcredit/');

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
            DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . $this->GetPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclusionAdminFiles))
                        continue;
                    \Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item);
                }
                closedir($dir);
            }
        }

        return true;

        /*if (Loader::includeModule($this->MODULE_ID)) {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ExampleTable::getTableName());
        }*/
    }
}
