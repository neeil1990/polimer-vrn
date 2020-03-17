<?
IncludeModuleLangFile(__FILE__);
Class arturgolubev_ecommerce extends CModule
{
	const MODULE_ID = 'arturgolubev.ecommerce';
	var $MODULE_ID = 'arturgolubev.ecommerce'; 
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';

	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = GetMessage("arturgolubev.ecommerce_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("arturgolubev.ecommerce_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("arturgolubev.ecommerce_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("arturgolubev.ecommerce_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		// COption::SetOptionString("sale", "expiration_processing_events", 'Y');
		
		RegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevEcommerce', 'ProtectEpilogStart');
		
		RegisterModuleDependences('main', 'OnEndBufferContent', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBufferContent');
		RegisterModuleDependences('sale', 'OnBasketAdd', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBasketAdd');
		RegisterModuleDependences('sale', 'OnBeforeBasketDelete', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBasketDelete');
		RegisterModuleDependences('sale', 'OnOrderAdd', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onOrderAdd');
		
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevEcommerce', 'ProtectEpilogStart');
		
		UnRegisterModuleDependences('main', 'OnEndBufferContent', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBufferContent');
		UnRegisterModuleDependences('sale', 'OnBasketAdd', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBasketAdd');
		UnRegisterModuleDependences('sale', 'OnBeforeBasketDelete', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onBasketDelete');
		UnRegisterModuleDependences('sale', 'OnOrderAdd', "arturgolubev.ecommerce", 'CArturgolubevEcommerce', 'onOrderAdd');
		
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components'))
		{
			if ($dir = opendir($p))
			{
				while (false !== $item = readdir($dir))
				{
					if ($item == '..' || $item == '.')
						continue;
					CopyDirFiles($p.'/'.$item, $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/'.$item, $ReWrite = True, $Recursive = True);
				}
				closedir($dir);
			}
		}
		
		$mPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/";
		
		CopyDirFiles($mPath.$this->MODULE_ID."/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",true,true);
		CopyDirFiles($mPath.$this->MODULE_ID."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js",true,true);
		
		return true;
	}

	function UnInstallFiles()
	{
		if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components'))
		{
			if ($dir = opendir($p))
			{
				while (false !== $item = readdir($dir))
				{
					if ($item == '..' || $item == '.' || !is_dir($p0 = $p.'/'.$item))
						continue;

					$dir0 = opendir($p0);
					while (false !== $item0 = readdir($dir0))
					{
						if ($item0 == '..' || $item0 == '.')
							continue;
						DeleteDirFilesEx('/bitrix/components/'.$item.'/'.$item0);
					}
					closedir($dir0);
				}
				closedir($dir);
			}
		}
		
		DeleteDirFilesEx("/bitrix/tools/".self::MODULE_ID);
		DeleteDirFilesEx("/bitrix/js/".self::MODULE_ID);
		
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $DOCUMENT_ROOT;
		
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
		
		$APPLICATION->IncludeAdminFile(GetMessage("MOD_INST_OK"), $DOCUMENT_ROOT."/bitrix/modules/arturgolubev.ecommerce/install/success_install.php");
	}

	function DoUninstall()
	{
		global $APPLICATION;
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallDB();
		$this->UnInstallFiles();
	}
}
?>
