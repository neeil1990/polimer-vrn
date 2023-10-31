<?
namespace Arturgolubev\Htmlcompressor;

class Tools {
	function getCurPage(){
		global $APPLICATION;
		return $APPLICATION->GetCurPage(false);
	}
	function GetUserRight(){
		global $APPLICATION;
		return $APPLICATION->GetUserRight("fileman");
	}
}