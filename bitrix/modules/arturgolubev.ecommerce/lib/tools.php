<?
namespace Arturgolubev\Ecommerce;

use \Arturgolubev\Ecommerce\Unitools as UTools;

class Tools {
	const MODULE_ID = 'arturgolubev.ecommerce';
	var $MODULE_ID = 'arturgolubev.ecommerce';
	
	function checkDisable(){
		if(!isset(UTools::$storage["main"]["disable"]))
		{
			UTools::setStorage("main", "disable",
				IntVal(UTools::getSetting('off_mode') == 'Y' || UTools::getSetting('off_mode_'.SITE_ID) == 'Y' || !\CModule::IncludeModule(self::MODULE_ID)));
		}
		
		$r = UTools::getStorage("main", "disable");
		
		return $r;
	}
	
	function isOrderPage(){
		global $APPLICATION;
		return (UTools::getSiteSettingEx('order_page') && $APPLICATION->GetCurPage(false) == UTools::getSiteSettingEx('order_page'));
	}
	
	function textSafeMode($text, $htsc = false){
		if($htsc) $text = htmlspecialcharsbx($text);
		
		$text = str_replace(array("'", '"'), array("", ""), $text);
		$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
		
		return $text;
	}
	
	function addScriptToHead($result){
		$r = UTools::getStorage("scripts", "move_footer");
		$r .= str_replace(array("\r\n", "\r", "\n"), '',  $result);
		
		UTools::setStorage("scripts", 'move_footer', $r);
	}
}