<?
IncludeModuleLangFile(__FILE__);
Class arturgolubev_lazyload extends CModule
{
	const MODULE_ID = 'arturgolubev.lazyload';
	var $MODULE_ID = 'arturgolubev.lazyload'; 
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
		$this->MODULE_NAME = GetMessage("arturgolubev.lazyload_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("arturgolubev.lazyload_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("arturgolubev.lazyload_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("arturgolubev.lazyload_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		RegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevLazyload', 'onEpilog');
		RegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevLazyload', 'onBufferContent');
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevLazyload', 'onEpilog');
		UnRegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevLazyload', 'onBufferContent');
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
		$mPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/";
		
		CopyDirFiles($mPath.$this->MODULE_ID."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js",true,true);
		CopyDirFiles($mPath.$this->MODULE_ID."/install/css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css",true,true);
		CopyDirFiles($mPath.$this->MODULE_ID."/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images",true,true);
		
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx("/bitrix/js/".self::MODULE_ID);
		DeleteDirFilesEx("/bitrix/css/".self::MODULE_ID);
		DeleteDirFilesEx("/bitrix/images/".self::MODULE_ID);
		
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $DOCUMENT_ROOT;
		
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
		
		$APPLICATION->IncludeAdminFile(GetMessage("MOD_INST_OK"), $DOCUMENT_ROOT."/bitrix/modules/arturgolubev.lazyload/install/success_install.php");
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
