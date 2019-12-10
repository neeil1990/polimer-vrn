<?
namespace Arturgolubev\Lazyload;

class Tools {
	const MODULE_ID = 'arturgolubev.lazyload';
	var $MODULE_ID = 'arturgolubev.lazyload'; 
	
	function checkStatus(){
		return (!defined('LOCK_LAZYLOAD') && !defined('ADMIN_SECTION') && $_SERVER['REQUEST_METHOD'] != 'POST' && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') !== 0);
	}
	function checkAjax(){
		$check = (strtolower($_REQUEST['ajax']) == 'y' || (isset($_REQUEST["bxajaxid"]) && strlen($_REQUEST["bxajaxid"]) > 0)) ? 0 : 1;
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') $check = 0;
		return $check;
	}
	
	function getSetting($name, $def = false){
		return trim(\COption::GetOptionString(self::MODULE_ID, $name, $def));
	}
	function getSiteSetting($name, $def = false){
		return trim(\COption::GetOptionString(self::MODULE_ID, $name.'_'.SITE_ID, $def));
	}
	function getSiteSettingEx($name, $def = false){
		$val = trim(\COption::GetOptionString(self::MODULE_ID, $name.'_'.SITE_ID));
		if(!$val) $val = trim(\COption::GetOptionString(self::MODULE_ID, $name));
		if(!$val && $def) $val = $def;
		
		return $val;
	}
	
	
	
	
	
	function checkDisable(){		
		return (self::getSetting('enabled_'.SITE_ID) == 'Y') ? true : false;
	}
}