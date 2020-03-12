<?
IncludeModuleLangFile(__FILE__);
Class arturgolubev_cssinliner extends CModule
{
	const MODULE_ID = 'arturgolubev.cssinliner';
	var $MODULE_ID = 'arturgolubev.cssinliner'; 
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
		$this->MODULE_NAME = GetMessage("arturgolubev.cssinliner_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("arturgolubev.cssinliner_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("arturgolubev.cssinliner_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("arturgolubev.cssinliner_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		RegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevCssinliner', 'onBufferContent', 90);
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevCssinliner', 'onBufferContent');
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
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
	}

	function DoUninstall()
	{
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallDB();
		$this->UnInstallFiles();
	}
}
?>
