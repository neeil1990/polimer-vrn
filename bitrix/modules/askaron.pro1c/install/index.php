<?
IncludeModuleLangFile(__FILE__);

if (class_exists('askaron_pro1c')) return;

class askaron_pro1c extends CModule
{  
	var $MODULE_ID = "askaron.pro1c";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $PARTNER_NAME;
	public $PARTNER_URI;
	public $MODULE_GROUP_RIGHTS = 'Y';
	// first modules '8.0.7', 2009-06-29
	// htmlspecialcharsbx was added in '11.5.9', 2012-09-13
	
	public $NEED_MAIN_VERSION = '8.0.7';
	public $NEED_MODULES = array();

	public $MY_DIR = "bitrix";
	
	public function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace('\\', '/', __FILE__);
		$dir = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($dir.'/version.php');

		$check_last = "/local/modules/".$this->MODULE_ID."/install/index.php";
		$check_last_len = strlen($check_last);

		if ( substr($path, -$check_last_len) == $check_last )
		{
			$this->MY_DIR = "local";
		}
		
		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
		
		// !Twice! Marketplace bug. 2013-03-13
		$this->PARTNER_NAME = "Askaron Systems";
		$this->PARTNER_NAME = GetMessage("ASKARON_PRO1C_PARTNER_NAME");
		$this->PARTNER_URI = 'http://askaron.ru/';

		$this->MODULE_NAME = GetMessage('ASKARON_PRO1C_MODULE_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('ASKARON_PRO1C_MODULE_DESCRIPTION');
	}

	public function DoInstall()
	{
		global $APPLICATION;

		global $askaron_pro1c_global_errors;
		$askaron_pro1c_global_errors = array();

		if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES))
			foreach ($this->NEED_MODULES as $module)
				if (!IsModuleInstalled($module))
					$askaron_pro1c_global_errors[] = GetMessage('ASKARON_PRO1C_NEED_MODULES', array('#MODULE#' => $module));
				
		if ( strlen($this->NEED_MAIN_VERSION) > 0  && version_compare(SM_VERSION, $this->NEED_MAIN_VERSION) < 0)
		{
			$askaron_pro1c_global_errors[] = GetMessage( 'ASKARON_PRO1C_NEED_RIGHT_VER', array('#NEED#' => $this->NEED_MAIN_VERSION) );
		}
		
		if ( count( $askaron_pro1c_global_errors ) == 0 )
		{
			RegisterModule("askaron.pro1c");
			RegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID, "CAskaronPro1C", "OnPageStartHandler", "20");
			//RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePrologHandler", "20");
			RegisterModuleDependences("main", "OnProlog", $this->MODULE_ID, "CAskaronPro1C", "OnPrologHandler", "20");
			
			RegisterModuleDependences("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementAddHandler", "500000");
			RegisterModuleDependences("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementUpdateHandler", "500000");
			RegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementDeleteHandler", "500000");
			
			RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", $this->MODULE_ID, "CAskaronPro1C", "OnAfterIBlockElementAddHandler", "20");
			RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnAfterIBlockElementUpdateHandler", "20");
			
			RegisterModuleDependences("catalog", "OnBeforePriceAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceAddHandler", "500000");
			RegisterModuleDependences("catalog", "OnBeforePriceUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceUpdateHandler", "500000");
			RegisterModuleDependences("catalog", "OnBeforePriceDelete", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceDeleteHandler", "500000");

			RegisterModuleDependences("catalog", "OnBeforeProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductAddHandler", "90");
			RegisterModuleDependences("catalog", "OnBeforeProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductUpdateHandler", "90");
			
			RegisterModuleDependences("catalog", "OnBeforeProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductAddHandlerLog", "500000");
			RegisterModuleDependences("catalog", "OnBeforeProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductUpdateHandlerLog", "500000");

			RegisterModuleDependences("catalog", "OnStoreProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductAddHandler", "500000");
			RegisterModuleDependences("catalog", "OnStoreProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductUpdateHandler", "500000");
			RegisterModuleDependences("catalog", "OnStoreProductDelete", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductDeleteHandler", "500000");
			
			RegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "CAskaronPro1C", "OnEndBufferContentHandler", "500000");
			RegisterModuleDependences('catalog', 'OnSuccessCatalogImport1C', $this->MODULE_ID, "CAskaronPro1C", "OnSuccessCatalogImport1CHandler", "500000");
			
			RegisterModuleDependences("pull", "OnGetDependentModule", $this->MODULE_ID, "CAskaronPro1C", "OnGetDependentModuleHandler" );

			RegisterModuleDependences("main", "OnAfterSetOption_secure_1c_exchange", $this->MODULE_ID, "CAskaronPro1C", "OnAfterSetOption_secure_1c_exchange" );
			RegisterModuleDependences("main", "OnAfterSetOption_DEFAULT_SKIP_SOURCE_CHECK", $this->MODULE_ID, "CAskaronPro1C", "OnAfterSetOption_DEFAULT_SKIP_SOURCE_CHECK" );

			RegisterModuleDependences("main", "OnBeforeUserAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeUserAdd", "500000");
			RegisterModuleDependences("main", "OnAfterUserAdd", $this->MODULE_ID, "CAskaronPro1C", "OnAfterUserAdd", "20");

			RegisterModuleDependences("main", "OnBeforeUserUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeUserUpdate", "500000");
			RegisterModuleDependences("main", "OnAfterUserUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnAfterUserUpdate", "20");



			//$random_value = COption::GetOptionString( $this->MODULE_ID, "random_value" );
			//if ( $random_value != "" && $random_value != "11111111")
			//{
				COption::SetOptionString( $this->MODULE_ID, "random_value", randString( 8, array( "abcdefghijklnmopqrstuvwxyz", "0123456789" ) ) );
			//}
			
			$this->InstallDB();
			$this->InstallFiles();

			if ( CModule::IncludeModule( $this->MODULE_ID ) )
			{
				CAskaronPro1CCache::SetAgentByOptions();
			}
		}
		
		$APPLICATION->IncludeAdminFile( GetMessage("ASKARON_PRO1C_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/step.php");
		return true;
	}

	public function DoUninstall()
	{
		global $APPLICATION;

		CAgent::RemoveModuleAgents( $this->MODULE_ID );

		$this->UnInstallFiles();
		$this->UnInstallDB();



		UnRegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID, "CAskaronPro1C", "OnPageStartHandler");
		//UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePrologHandler");
		UnRegisterModuleDependences("main", "OnProlog", $this->MODULE_ID, "CAskaronPro1C", "OnPrologHandler");
		
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementAddHandler");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementUpdateHandler");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeIBlockElementDeleteHandler");

		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", $this->MODULE_ID, "CAskaronPro1C", "OnAfterIBlockElementAddHandler");
		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnAfterIBlockElementUpdateHandler");
		
		
		UnRegisterModuleDependences("catalog", "OnBeforePriceAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceAddHandler");
		UnRegisterModuleDependences("catalog", "OnBeforePriceUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceUpdateHandler");
		UnRegisterModuleDependences("catalog", "OnBeforePriceDelete", $this->MODULE_ID, "CAskaronPro1C", "OnBeforePriceDeleteHandler");

		UnRegisterModuleDependences("catalog", "OnBeforeProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductAddHandler");
		UnRegisterModuleDependences("catalog", "OnBeforeProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductUpdateHandler");
			
		UnRegisterModuleDependences("catalog", "OnBeforeProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductAddHandlerLog");
		UnRegisterModuleDependences("catalog", "OnBeforeProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductUpdateHandlerLog");		
		
		//UnRegisterModuleDependences("catalog", "OnBeforeProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductAddHandler");
		//UnRegisterModuleDependences("catalog", "OnBeforeProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeProductUpdateHandler");
		
		UnRegisterModuleDependences("catalog", "OnStoreProductAdd", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductAddHandler");
		UnRegisterModuleDependences("catalog", "OnStoreProductUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductUpdateHandler");
		UnRegisterModuleDependences("catalog", "OnStoreProductDelete", $this->MODULE_ID, "CAskaronPro1C", "OnStoreProductDeleteHandler");
		
		UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "CAskaronPro1C", "OnEndBufferContentHandler");
		UnRegisterModuleDependences('catalog', 'OnSuccessCatalogImport1C', $this->MODULE_ID, "CAskaronPro1C", "OnSuccessCatalogImport1CHandler");
		
		UnRegisterModuleDependences("pull", "OnGetDependentModule", $this->MODULE_ID, "CAskaronPro1C", "OnGetDependentModuleHandler" );

		UnRegisterModuleDependences("main", "OnAfterSetOption_secure_1c_exchange", $this->MODULE_ID, "CAskaronPro1C", "OnAfterSetOption_secure_1c_exchange" );
		UnRegisterModuleDependences("main", "OnAfterSetOption_DEFAULT_SKIP_SOURCE_CHECK", $this->MODULE_ID, "CAskaronPro1C", "OnAfterSetOption_DEFAULT_SKIP_SOURCE_CHECK" );

		UnRegisterModuleDependences("main", "OnBeforeUserAdd", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeUserAdd");
		UnRegisterModuleDependences("main", "OnAfterUserAdd", $this->MODULE_ID, "CAskaronPro1C", "OnAfterUserAdd");

		UnRegisterModuleDependences("main", "OnBeforeUserUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnBeforeUserUpdate");
		UnRegisterModuleDependences("main", "OnAfterUserUpdate", $this->MODULE_ID, "CAskaronPro1C", "OnAfterUserUpdate");


		UnRegisterModule('askaron.pro1c');

		$APPLICATION->IncludeAdminFile( GetMessage("ASKARON_PRO1C_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/unstep.php");
		return true;		
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/templates/",	$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/", true, true);
		CheckDirPath( $_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog_copy_askaron_pro1c/" );
	}

	function UnInstallFiles( $arParams = array() )
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFilesEx("/bitrix/templates/askaron_pro1c_empty/"); //template		
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/".$this->MY_DIR."/modules/".$this->MODULE_ID."/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/".$this->MODULE_ID."/");//icons

		// default log-file
		$sites=CSite::GetList(($b=""), ($o=""));
		while ( $arSite=$sites->Fetch() )
		{
			$log_file_name = $arSite["ABS_DOC_ROOT"]."/log_askaron_pro1c__".COption::GetOptionString( $this->MODULE_ID, "random_value" ).".txt";
			if ( is_file($log_file_name) )
			{
				@unlink($log_file_name);
			}
		}
	}
	
	function InstallDB()
	{
		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		return true;
	}
}
?>