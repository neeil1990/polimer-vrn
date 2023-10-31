<?
namespace Arturgolubev\Ozon\Card;

use \Bitrix\Main\Loader;

use \Arturgolubev\Ozon\Tools as Helper;

class Tabhelper {
	static function getElementOzonID($sid, $iblock, $element){
		$sett = Helper::getCatalogSettings($sid);
		if(Loader::includeModule("iblock") && $sett["IBLOCKS"][$iblock]["OZONID_PROP"]){
			$arSelect = array("ID", "NAME", $sett["IBLOCKS"][$iblock]["OZONID_PROP"]);
			$arFilter = array("IBLOCK_ID"=>$iblock, "ID"=>$element);
			$res = \CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize"=>1), $arSelect);
			while($ob = $res->GetNextElement()){
				$arFields = $ob->GetFields();
				$ozonID = $arFields[$sett["IBLOCKS"][$iblock]["OZONID_PROP_VALUE"]];
			}
		}
		
		return $ozonID;
	}
	
	static function checkElementExportStatus($sid, $iblock, $element){
		$sett = Helper::getCatalogSettings($sid);
		if(Loader::includeModule("iblock") && $sett["IBLOCKS"][$iblock]["FILTER_BASE"]){
			$arFilter = $sett["IBLOCKS"][$iblock]["FILTER_BASE"];
			$arFilter['ID'] = $element;
			$res = \CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize"=>1), array("ID", "NAME"));
			while($ob = $res->GetNextElement()){
				$arFields = $ob->GetFields();
				return 1;
			}
		}
		return 0;
	}
}