<?
IncludeModuleLangFile(__FILE__);
Class arturgolubev_htmlcompressor extends CModule
{
	const MODULE_ID = 'arturgolubev.htmlcompressor';
	var $MODULE_ID = 'arturgolubev.htmlcompressor'; 
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
		$this->MODULE_NAME = GetMessage("arturgolubev.htmlcompressor_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("arturgolubev.htmlcompressor_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("arturgolubev.htmlcompressor_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("arturgolubev.htmlcompressor_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		RegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevHtmlcompressor', 'onBufferContent');
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevHtmlcompressor', 'onBufferContent');
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
		global $APPLICATION;
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
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
