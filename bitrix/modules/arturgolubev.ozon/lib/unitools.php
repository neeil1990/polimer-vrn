<?
namespace Arturgolubev\Ozon; //2.4.1

use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Config\Option;

class Unitools {
	const MODULE_ID = 'arturgolubev.ozon';
	
	// storage
	public static $storage = array();
	public static function setStorage($type, $name, $value){
		if(!isset(self::$storage[$type])){
			self::$storage[$type] = [];
		}
		
		self::$storage[$type][$name] = $value;
	}
	public static function getStorage($type, $name){
		if(!isset(self::$storage[$type])){
			self::$storage[$type] = [];
		}
		
		return (isset(self::$storage[$type][$name]) ? self::$storage[$type][$name] : null);
	}
	
	// settings
	static function getSettingDB($name, $def = false){
		return trim(Option::get(self::MODULE_ID, $name, $def));
	}
	
	static function setSetting($name, $value){
		Option::set(self::MODULE_ID, $name, $value);
	}
	static function getSetting($name, $def = false){
		if(!isset(self::$storage["setting"][$name])){
			self::setStorage("setting", $name, trim(Option::get(self::MODULE_ID, $name, $def)));
		}
		$r = self::getStorage("setting", $name);
		return $r;
	}
	
	static function getSiteSetting($name, $def = false){
		if(!isset(self::$storage["setting_site"][$name])){
			self::setStorage("setting_site", $name, trim(Option::get(self::MODULE_ID, $name.'_'.SITE_ID, $def)));
		}
		$r = self::getStorage("setting_site", $name);
		return $r;
	}
	
	static function getSiteSettingEx($name, $def = false){
		if(!isset(self::$storage["setting_site_ex"][$name])){			
			$val = trim(Option::get(self::MODULE_ID, $name.'_'.SITE_ID, Option::get(self::MODULE_ID, $name, $def)));
			self::setStorage("setting_site_ex", $name, $val);
		}else{
			$val = self::getStorage("setting_site_ex", $name);
		}
		return $val;
	}
	
	// globals
	static function isAdmin(){
		global $USER;
		if(!is_object($USER)) $USER = new CUser();
		return $USER->IsAdmin();
	}
	
	static function addJs($script){
		// global $APPLICATION; $APPLICATION->AddHeadScript($script);
		Asset::getInstance()->addJs($script);
	}
	static function addCss($script, $p2 = true){
		// global $APPLICATION; $APPLICATION->SetAdditionalCSS($script, $p2);
		Asset::getInstance()->addCss($script, $p2);
	}
	static public function getCurPage(){
		global $APPLICATION;
		return $APPLICATION->GetCurPage(false);
	}
	
	// simple
	static function textOneLine($text){
		return str_replace(array("\r\n", "\r", "\n"), '',  $text);
	}
	
	static function textSafeMode($text, $htsc = false){
		if($htsc) $text = htmlspecialchars_decode($text);
		
		$text = str_replace(array("'", '"', '&', "\r\n", "\r", "\n"), "", $text);
		$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
		
		if($htsc) $text = htmlspecialcharsbx($text);
		
		return $text;
	}
	
	// sort
	static function sort_by_sort_asc($a, $b){
		if ($a == $b){return 0;}
		return ($a["SORT"] < $b["SORT"]) ? -1 : 1;
	}
	static function sort_by_sort_desc($a, $b){
		if ($a == $b){return 0;}
		return ($a["SORT"] > $b["SORT"]) ? -1 : 1;
	}
	
	// regular
	static function onComposite(){
		return file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled");
	}
	static function isHtmlPage($page){
		if(!defined("AG_CHECK_DOCTYPE")){
			$t = (stripos(substr($page,0,512), '<!DOCTYPE') === false) ? 0 : 1;
			define('AG_CHECK_DOCTYPE', $t);
		}
		return AG_CHECK_DOCTYPE;
	}
	static function isAdminPage(){
		if(!isset(self::$storage["main"]["is_admin_page"])){
			$r = 0;
			if(defined("ADMIN_SECTION") && ADMIN_SECTION == true) $r = 1;
			if(strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') === 0) $r = 1;
			if(strpos($_SERVER['PHP_SELF'], BX_ROOT.'/tools') === 0) $r = 1;
			
			self::setStorage("main", "is_admin_page", $r);
		}else{
			$r = self::getStorage("main", "is_admin_page");
		}
		
		return $r;
	}
	
	static function checkStatus(){
		if(!isset(self::$storage["main"]["status"]))
		{
			$r = (self::isAdminPage() || $_SERVER['REQUEST_METHOD'] == 'POST') ? 0 : 1;
			self::setStorage("main", "status", $r);
		}
		else
			$r = self::getStorage("main", "status");
		
		return $r;
	}
	
	static function checkAjax(){
		$check = (strtolower($_REQUEST['ajax']) == 'y' || (isset($_REQUEST["bxajaxid"]) && strlen($_REQUEST["bxajaxid"]) > 0)) ? 0 : 1;
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') $check = 0;
		return $check;
	}
	
	static function checkPageException($pages){
		if($pages)
		{
			global $APPLICATION;
			
			$cur = $APPLICATION->GetCurPage(false);
			$curParams = $APPLICATION->GetCurPageParam();
			
			$ar_pages = explode("\n",$pages);
			foreach($ar_pages as $checkValue)
			{
				$checkValue = trim($checkValue);
				if(!$checkValue) continue;
				
				$pattern = '/^'.str_replace(array('/', '*'), array('\/', '.*'), $checkValue).'$/sU';
				
				if(preg_match($pattern, $cur) || preg_match($pattern, $curParams))
					return 0;
			}
		}
		
		return 1;
	}
	
	static function explodeByEOL($str){
		$ar = explode(PHP_EOL, $str);
		
		if(is_array($ar)){
			foreach($ar as $k=>$ex){
				$ar[$k] = $ex = trim($ex);
				if($ex == '') unset($ar[$k]);
			}
			
			$ar = array_values($ar);
		}
		
		return $ar;
	}
	
	static function checkModuleVersion($module, $version){
		$saleModuleInfo = \CModule::CreateModuleObject($module);
		return CheckVersion($saleModuleInfo->MODULE_VERSION, $version);
	}
	
	// append
	static function addBodyScript($script, $oldBuffer){
		$search = '</body>';
		$replace = $script. PHP_EOL .$search;
		
		$bufferContent = $oldBuffer;
		
		if(substr_count($oldBuffer, $search) == 1)
		{
			$bufferContent = str_replace($search, $replace, $oldBuffer);
		}
		else
		{
			$bodyEnd = self::getLastPositionIgnoreCase($oldBuffer, $search);
			if ($bodyEnd !== false)
			{
				$bufferContent = substr_replace($oldBuffer, $replace, $bodyEnd, strlen($search));
			}
		}
		
		return $bufferContent;
	}
	
	static function getLastPositionIgnoreCase($haystack, $needle, $offset = 0){
		if (defined("BX_UTF")){
			if (function_exists("mb_orig_strripos")){
				return mb_orig_strripos($haystack, $needle, $offset);
			}
			return mb_strripos($haystack, $needle, $offset, "latin1");
		}
		return strripos($haystack, $needle, $offset);
	}
	
	static function getFirstPositionIgnoreCase($haystack, $needle, $offset = 0){
		if (defined("BX_UTF")){
			if (function_exists("mb_orig_stripos")){
				return mb_orig_stripos($haystack, $needle, $offset);
			}
			return mb_stripos($haystack, $needle, $offset, "latin1");
		}
		return stripos($haystack, $needle, $offset);
	}
}