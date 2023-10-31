<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2016 Altair TK. (http://www.wsrubi.ru)
 */
$PathInstall = dirname(__FILE__);
IncludeModuleLangFile(__FILE__);
include($PathInstall.'/version.php');
if (class_exists('wsrubi_smtp')) return;

class wsrubi_smtp extends CModule
{
	var $MODULE_ID = "wsrubi.smtp";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME='';
	public $MODULE_DESCRIPTION='';
	public $PARTNER_NAME;
	public $PARTNER_URI;
	public $MODULE_GROUP_RIGHTS = 'N';

	//init.php
	static protected $pathFileInit=null;
	//str module include
	static protected $strInclude=null;

	public function __construct()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		self::$pathFileInit=$DOCUMENT_ROOT.'/bitrix/php_interface/init.php';
		self::$strInclude='include_once($_SERVER[\'DOCUMENT_ROOT\']."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");';

		$arModuleVersion = array();

		$path = dirname(__FILE__);
		include($path.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->PARTNER_NAME = GetMessage('WSRUBI_PARTNER_NAME');
		$this->PARTNER_URI = GetMessage('WSRUBI_PARTNER_URI');

		$this->MODULE_NAME = GetMessage('WSRUBI_MODULE_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('WSRUBI_MODULE_DESCRIPTION');
	}

	
	
	function DoInstall()
	{
		global $APPLICATION;
		
		if(function_exists("custom_mail")){
			$APPLICATION->throwException(GetMessage('WSRUBI_ERROR'));			
			return false;
		}
		
		if (!IsModuleInstalled("wsrubi.smtp"))
		{
			$this->InstallDB();
			$this->InstallEvents();
			$this->InstallFiles();
			
		}
		return true;
	}

	function DoUninstall()
	{
		$this->UnInstallFiles();
		$this->UnInstallDB();
		$this->UnInstallEvents();
		
		return true;
	}
	
	
	function InstallDB() {
		
		RegisterModule("wsrubi.smtp");	
		return true;
	
			
	}
	
	function UnInstallDB()
	{
		UnRegisterModule("wsrubi.smtp");
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

	function InstallFiles()
	{
		/*if(file_exists(self::$pathFileInit)){
			$text=file_get_contents(self::$pathFileInit);
			$text=self::InjectionNewStr($text);
			return file_put_contents(self::$pathFileInit,$text);
		}else{
			$res = file_put_contents(self::$pathFileInit,"<?\r\n".self::$strInclude."\r\n");
			return $res;
		}*/
	    return true;
	}
	
	function UnInstallFiles()
	{
		global $DOCUMENT_ROOT;
		/*if (file_exists(self::$pathFileInit))
			{
				$text = file_get_contents(self::$pathFileInit);
				if(strpos($text,'wsrubismtp.php')!==FALSE) {
                    $newLineSymbol = self::GetNewLineSymbol($text);
                    $find = array(
                        $newLineSymbol . self::$strInclude . $newLineSymbol,
                        $newLineSymbol . 'include_once(' . $DOCUMENT_ROOT . '"/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");' . $newLineSymbol
                    );
                    $text = str_replace($find, '', $text);
                    file_put_contents(self::$pathFileInit, $text);
                }
			}*/
		return true;
	}

	/**
	 * @param string $text
	 */
	static protected function InjectionNewStr($text){
		$newLineSymbol=self::GetNewLineSymbol($text);
		$pos=strpos($text,'?>');
		if($pos!==FALSE){
			$text=str_replace(array('?>'),$newLineSymbol.self::$strInclude.$newLineSymbol.'?>',$text);
		}else{
			$text.=$newLineSymbol.self::$strInclude.$newLineSymbol;
		}
		return $text;
	}

	/**
	 * @param string $text
	 */
	static protected function GetNewLineSymbol($text){
		if(strpos($text,"\r\n")!==FALSE){
			return "\r\n";
		}else
			return "\n";
	}

}
?>