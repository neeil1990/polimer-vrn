<?

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
Loc::loadMessages(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");

Class sotbit_seometa extends CModule
{
	const MODULE_ID = 'sotbit.seometa';
	var $MODULE_ID = 'sotbit.seometa';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';

	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("SOTBIT_SEOMETA_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("SOTBIT_SEOMETA_MODULE_DESC");
		$this->PARTNER_NAME = Loc::getMessage("SOTBIT_SEOMETA_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("SOTBIT_SEOMETA_PARTNER_URI");
	}

	function InstallEvents()
	{
		RegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlGroup", "GetControlDescr");
		RegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlIBlockFields", "GetControlDescr");
		RegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlIBlockProps", "GetControlDescr");

		RegisterModuleDependences("iblock", "OnTemplateGetFunctionClass", self::MODULE_ID, "CSeoMetaTags", "Event");
		RegisterModuleDependences("iblock", "OnTemplateGetFunctionClassHandler", self::MODULE_ID, "CSeoMetaTags", "EventHandler");

        RegisterModuleDependences("main", "OnAdminIBlockSectionEdit", self::MODULE_ID, "CSeoMetaEvents", "OnInit");
        RegisterModuleDependences("main", "OnPageStart", self::MODULE_ID, "CSeoMetaEvents", "PageStart");
        RegisterModuleDependences("main", "OnAfterEpilog", self::MODULE_ID, "CSeoMetaEvents", "EpilogAfter");
		RegisterModuleDependences("main", "OnBuildGlobalMenu", self::MODULE_ID, 'CSeoMetaEvents', 'OnBuildGlobalMenuHandler');
		RegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CSeoMetaEvents', 'ChangeContent');

		RegisterModuleDependences("search", "OnReindex", self::MODULE_ID, "CSeoMetaEvents", "OnReindexHandler");
		RegisterModuleDependences("search", "OnAfterIndexAdd", self::MODULE_ID, "CSeoMetaEvents", "OnAfterIndexAddHandler");

		$rsSites = CSite::GetList(
			$by = "sort",
			$order = "desc",
			[
				"ACTIVE" => "Y"
			]
		);

		while($arSite = $rsSites->Fetch()) {
			COption::SetOptionString(self::MODULE_ID,"NO_INDEX_".$arSite['LID'],"N");
		}

		return true;
	}

	function UnInstallEvents()
	{
		UnRegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlGroup", "GetControlDescr");
		UnRegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlIBlockFields", "GetControlDescr");
		UnRegisterModuleDependences(self::MODULE_ID, "OnCondCatControlBuildListSM", self::MODULE_ID, "SMCatalogCondCtrlIBlockProps", "GetControlDescr");

		UnRegisterModuleDependences("iblock", "OnTemplateGetFunctionClass", self::MODULE_ID, "CSeoMetaTags", "Event");
		UnRegisterModuleDependences("iblock", "OnTemplateGetFunctionClassHandler", self::MODULE_ID, "CSeoMetaTags", "EventHandler");

        UnRegisterModuleDependences("main", "OnAdminIBlockSectionEdit", self::MODULE_ID, "CSeoMetaEvents", "OnInit");
        UnRegisterModuleDependences("main", "OnPageStart", self::MODULE_ID, "CSeoMetaEvents", "PageStart");
        UnRegisterModuleDependences("main", "OnAfterEpilog", self::MODULE_ID, "CSeoMetaEvents", "EpilogAfter");
		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", self::MODULE_ID, 'CSeoMetaEvents', 'OnBuildGlobalMenuHandler');
		UnRegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CSeoMetaEvents', 'ChangeContent');

		UnRegisterModuleDependences("search", "OnReindex", self::MODULE_ID, "CSeoMetaEvents", "OnReindexHandler");
		UnRegisterModuleDependences("search", "OnAfterIndexAdd", self::MODULE_ID, "CSeoMetaEvents", "OnAfterIndexAddHandler");

		return true;
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/files/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/", true, true);

		if (
			is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components')
			&& $dir = opendir($p)
		) {
			while (false !== $item = readdir($dir))
			{
				if ($item == '..' || $item == '.') {
					continue;
				}

				CopyDirFiles($p.'/'.$item, $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/'.$item, True, True);
			}

			closedir($dir);
		}

		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/files', $_SERVER['DOCUMENT_ROOT'].'/bitrix');
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/themes/.default/icons/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/icons");
		if (
			is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components')
			&& $dir = opendir($p)
		) {
			while (false !== $item = readdir($dir))
			{
				if ($item == '..' || $item == '.' || !is_dir($p0 = $p.'/'.$item)) {
					continue;
				}

				$dir0 = opendir($p0);
				while (false !== $item0 = readdir($dir0))
				{
					if ($item0 == '..' || $item0 == '.') {
						continue;
					}

					DeleteDirFilesEx('/bitrix/components/'.$item.'/'.$item0);
				}
				closedir($dir0);
			}

			closedir($dir);
		}

		return true;
	}

	function installDB()
	{
		global $DB;

		$DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/db/'.mb_strtolower($DB->type).'/install.sql');
	}

	function UnInstallDB()
	{
		//
	}

	function UnInstallAgent()
	{
		CModule::IncludeModule('main');
		CAgent::RemoveModuleAgents("sotbit.seometa");
	}

	function DoInstall()
	{
		global $APPLICATION;

		if(version_compare(phpversion(), '7.4', '<')) {
			$APPLICATION->ThrowException(GetMessage('SOTBIT_SEOMETA_INSTALL_ERROR_VERSION_PHP'));
			return false;
		}

		$this->InstallFiles();
		$this->InstallDB();
		$this->InstallEvents();

		if($_REQUEST['step'] == 1)
		{
			if (!empty($_REQUEST['Email']) && !empty($_REQUEST['Name']) && !empty($_REQUEST['Phone'])) {
				if (filter_var($_REQUEST['Email'], FILTER_VALIDATE_EMAIL)) {
					if (preg_match("/^[0-9()+\-\s]+$/", $_REQUEST['Phone'])) {
                        $request = array(
                            'ACTION' => 'ADD',
                            'KEY' => md5("BITRIX". \Bitrix\Main\Application::getInstance()->getLicense()->getKey() . "LICENCE"),
                            'MODULE' => self::MODULE_ID,
                            'NAME' => $_REQUEST['Name'],
                            'EMAIL' => $_REQUEST['Email'],
                            'PHONE' => $_REQUEST['Phone'],
                            'SITE' => $_REQUEST['Site'],
                        );

                        $options = array(
                            'http' => array(
                                'method' => 'POST',
                                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                                'content' => json_encode($request)
                            )
                        );

                        $context = stream_context_create($options);
                        $answer = file_get_contents('https://www.sotbit.ru:443/api/datacollection/index.php', 0, $context);
                        ModuleManager::registerModule(self::MODULE_ID);
                    } else {
                        CAdminMessage::ShowMessage([
                            "MESSAGE" => Loc::getMessage(self::MODULE_ID . "_WRONG_NUMBER")
                        ]);
                    }
				}else{
					CAdminMessage::ShowMessage([
						"MESSAGE" => Loc::getMessage(self::MODULE_ID."_WRONG_EMAIL")
					]);
				}
			}else{
				CAdminMessage::ShowMessage([
					"MESSAGE" => Loc::getMessage(self::MODULE_ID."_EMPTY_FORM")
				]);
			}
		}
		else
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/step.php");
		}
		return true;
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $unstep;
		$unstep = IntVal($unstep);

		if($unstep < 2) {
			$APPLICATION->IncludeAdminFile(
				GetMessage("SOTBIT_SEOMETA_FORM_UNINSTALL_TITLE"),
				$_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/install/unstep.php"
			);
		}

		ModuleManager::unRegisterModule(self::MODULE_ID);
		$this->UnInstallFiles();

		if($unstep > 2 && $_REQUEST["save"] != 'on') {
			$DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/db/' . mb_strtolower($DB->type) . '/uninstall.sql');
		}

		$this->UnInstallDB();
		$this->UnInstallEvents();
		$this->UnInstallAgent();
	}
}
?>
