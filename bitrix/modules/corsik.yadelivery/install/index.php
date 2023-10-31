<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\Services\Table;

Loc::loadLanguageFile(__FILE__);

class corsik_yadelivery extends CModule
{

	var $MODULE_ID = "corsik.yadelivery";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	private array $arModuleDependences;
	private array $arRegisterEvent;
	private array $arCopyPath;

	public function __construct()
	{
		$arModuleVersion = [];
		$path = str_replace('\\', '/', __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($path . '/version.php');
		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
		$this->PARTNER_NAME = Loc::getMessage('CORSIK_DELIVERY_PARTNER_NAME');
		$this->PARTNER_URI = Loc::getMessage('CORSIK_DELIVERY_MODULE_URI');
		$this->MODULE_NAME = Loc::getMessage('CORSIK_DELIVERY_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CORSIK_DELIVERY_MODULE_DESCRIPTION');
		$this->bNotOutput = false;
		$this->arCopyPath = [
			//delete files in directory
			[__DIR__ . "/admin", "/bitrix/admin", 'N'],
			//delete directory
			[__DIR__ . "/js", "/bitrix/js/$this->MODULE_ID", 'Y'],
			[__DIR__ . "/css", "/bitrix/css/$this->MODULE_ID", 'Y'],
			[__DIR__ . "/tools", "/bitrix/tools/$this->MODULE_ID", 'Y'],
			[__DIR__ . "/themes/.default/$this->MODULE_ID.css", "/bitrix/themes/.default/$this->MODULE_ID.css", 'Y'],
			[
				__DIR__ . "/themes/.default/images/$this->MODULE_ID",
				"/bitrix/themes/.default/images/$this->MODULE_ID",
				'Y',
			],
			//delete the selected folder
			[__DIR__ . "/components", "/bitrix/components", 'corsik'],
		];
		$this->arModuleDependences = [
			[
				'sale',
				'OnSaleComponentOrderOneStepProcess',
				'\Corsik\YaDelivery\Handler',
				'sale_ComponentOrderOneStepProcess',
			],
			[
				'sale',
				'OnSaleComponentOrderResultPrepared',
				'\Corsik\YaDelivery\Handler',
				'sale_ComponentOrderResultPrepared',
			],
			[
				'sale',
				'onSaleDeliveryRestrictionsClassNamesBuildList',
				'\Corsik\YaDelivery\Handler',
				'sale_DeliveryRestrictions',
			],
			[
				'sale',
				'onSaleDeliveryHandlersClassNamesBuildList',
				'\Corsik\YaDelivery\Handler',
				'sale_DeliveryHandlers',
			],
			['main', 'OnEpilog', '\Corsik\YaDelivery\Handler', 'handlerOnEpilog'],
		];
		$this->arRegisterEvent = [
			['sale', 'OnSaleOrderBeforeSaved', '\Corsik\YaDelivery\Handler', 'sale_OrderBeforeSaved'],
		];
	}

	function DoInstall()
	{
		global $APPLICATION;
		$eventManager = EventManager::getInstance();
		if ($this->InstallFiles() && $this->InstallDB())
		{
			RegisterModule($this->MODULE_ID);

			foreach ($this->arModuleDependences as $item)
			{
				RegisterModuleDependences($item[0], $item[1], $this->MODULE_ID, $item[2], $item[3]);
			}

			foreach ($this->arRegisterEvent as $item)
			{
				$eventManager->registerEventHandler($item[0], $item[1], $this->MODULE_ID, $item[2], $item[3]);
			}

			$this->AddDelivery();

			$APPLICATION->IncludeAdminFile(Loc::getMessage("MOD_INST_OK"), __DIR__ . "/done.php");
		}
	}

	function getExistDelivery(string $className)
	{
		return Table::getList(['filter' => ['%CLASS_NAME' => $className]])->fetchRaw();
	}

	function AddDelivery()
	{
		Loader::includeModule('sale');
		$parentId = 0;

		$existYaDelivery = $this->getExistDelivery('YaDelivery\Delivery\YandexDeliveryHandler');
		$logoArray = CFile::MakeFileArray('/bitrix/themes/.default/images/corsik.yadelivery/ya_delivery_logo.png');
		$logotip = CFile::SaveFile($logoArray, '/sale/delivery/logotip');

		if ($existYaDelivery)
		{
			$parentId = $existYaDelivery['ID'];
		}
		else
		{
			$yaDelivery = [
				'PARENT_ID' => 0,
				'NAME' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_DELIVERY_NAME'),
				'ACTIVE' => "Y",
				'SORT' => 100,
				'CLASS_NAME' => "\Corsik\YaDelivery\Delivery\YandexDeliveryHandler",
				'LOGOTIP' => $logotip,
			];

			$res = Manager::add($yaDelivery);

			if (!$res->isSuccess())
			{
				return false;
			}

			$parentId = $res->getId();
		}

		$existProfileYaDelivery = $this->getExistDelivery('YaDelivery\Delivery\YandexDeliveryProfile');
		if (!$existProfileYaDelivery)
		{
			$yaDeliveryProfile = [
				'PARENT_ID' => $parentId,
				'NAME' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_PROFILE_NAME'),
				'ACTIVE' => "N",
				'SORT' => 100,
				'CLASS_NAME' => "\Corsik\YaDelivery\Delivery\YandexDeliveryProfile",
				'LOGOTIP' => $logotip,
			];

			Manager::add($yaDeliveryProfile);
		}
	}

	function InstallFiles(): bool
	{
		$errorDir = [];
		foreach ($this->arCopyPath as $paths)
		{
			if (!CopyDirFiles($paths[0], $_SERVER['DOCUMENT_ROOT'] . $paths[1], true, true))
			{
				$errorDir[] = $paths[1];
			}
		}
		return true;
	}

	function InstallDB(): bool
	{
		global $DB;
		$arrErrors = $DB->RunSQLBatch(dirname(__FILE__) . "/db/mysql/install.sql");
		$arrErrors = $DB->RunSQLBatch(dirname(__FILE__) . "/db/mysql/zones.sql");
		return !$arrErrors ? true : false;
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage('CRM_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/{$this->MODULE_ID}/install/unstep1.php");
		}
		elseif ($step == 2)
		{
			Loader::includeModule($this->MODULE_ID);
			$saveTable = $_REQUEST['save_table'];
			$saveData = $_REQUEST['save_data'];

			if ($saveTable !== 'Y')
			{
				$this->UnInstallDB();
			}

			if ($saveData !== 'Y')
			{
				$this->UnInstallData();
			}

			$this->UnInstallFiles();
			$eventManager = EventManager::getInstance();
			foreach ($this->arModuleDependences as $item)
			{
				UnRegisterModuleDependences($item[0], $item[1], $this->MODULE_ID, $item[2], $item[3]);
			}
			foreach ($this->arRegisterEvent as $item)
			{
				$eventManager->unRegisterEventHandler($item[0], $item[1], $this->MODULE_ID, $item[2], $item[3]);
			}
			UnRegisterModule($this->MODULE_ID);

			$APPLICATION->IncludeAdminFile(Loc::getMessage('CRM_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/{$this->MODULE_ID}/install/unstep2.php");
		}
	}

	function UnInstallDB(): bool
	{
		global $DB;
		$arrErrors = $DB->RunSQLBatch(dirname(__FILE__) . "/db/mysql/uninstall.sql");

		return !$arrErrors ? true : false;
	}

	//    function InstallData()
	//    {
	//        foreach ($this->arDefaultData as $key => $data) {
	//            array_walk($data, function ($d, $k, $n) {
	//                Option::set($this->MODULE_ID, $k . '_' . $n, $d);
	//            }, $key);
	//        }
	//    }
	//

	function UnInstallData()
	{
		foreach (Option::getForModule($this->MODULE_ID) as $optionKey => $optionValue)
		{
			Option::delete($this->MODULE_ID, ['name' => $optionKey]);
		}
	}

	function UnInstallFiles(): bool
	{
		foreach ($this->arCopyPath as $paths)
		{
			if ($paths[2] == 'Y')
			{
				Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . $paths[1]);
			}
			elseif ($paths[2] == 'N')
			{
				DeleteDirFiles($paths[0], $_SERVER['DOCUMENT_ROOT'] . $paths[1]);
			}
			else
			{
				Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . $paths[1] . "/" . $paths[2]);
			}
		}
		return true;
	}
}

?>
