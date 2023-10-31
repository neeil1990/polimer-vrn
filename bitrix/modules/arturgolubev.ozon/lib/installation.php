<?
if (!class_exists('agInstaHelperOZON')){
	class agInstaHelperOZON {
		const MODULE_ID = 'arturgolubev.ozon';
		
		static function checkMainStructure($site_id){
			IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/installation.php");
			
			if (CModule::IncludeModule("sale") && class_exists('\Bitrix\Sale\TradingPlatformTable') && !COption::GetOptionString(self::MODULE_ID, 'trading_platform')){
				$query = \Bitrix\Sale\TradingPlatformTable::getList(array(
					'filter' => array('CODE' => 'ag_ozon'),
					'order' => array('ID' => 'asc'),
				));
				
				if(!($row = $query->fetch())){
					$fields = array();
					$map = \Bitrix\Sale\TradingPlatformTable::getMap();
					foreach($map as $k=>$v){
						if(!$v['autocomplete'])
							$fields[$k] = '';
					}
					
					$fields['CODE'] = 'ag_ozon';
					$fields['ACTIVE'] = 'N';
					$fields['NAME'] = GetMessage("ARTURGOLUBEV_OZON_INSTALL_PLATFORM_ADD_NAME");
					$fields['DESCRIPTION'] = GetMessage("ARTURGOLUBEV_OZON_INSTALL_PLATFORM_ADD_DESCRIPTION");
					
					$addResult = \Bitrix\Sale\TradingPlatformTable::add($fields);
					if ($addResult->isSuccess()){
						COption::SetOptionString(self::MODULE_ID, 'trading_platform', $addResult->getId());
					}
				}
			}
			
			$resCheck = array(
				"success" => 0,
				"error" => ''
			);
			
			$iblockTYPE = "arturgolubev_services";
			
			if(!CModule::IncludeModule("iblock")){
				$resCheck["error"] = 'no_module_iblock';
				return $resCheck;
			}
			
			$ibRes = \CIBlockType::GetList(array("SORT"=>"ASC"), array("ID"=>$iblockTYPE));
			if (!($iblockType = $ibRes->GetNext())){
				$ibType = new \CIBlockType();
				$arFields = Array(
					'ID'=>$iblockTYPE,
					'SECTIONS'=>'Y',
					'IN_RSS'=>'N',
					'SORT'=>900,
					'LANG'=>Array(
						'ru'=>Array(
								'NAME'=>GetMessage("AG_IB_SERVISE_NAME")
							)
						)
				);
				if(!$ibType->Add($arFields)){
					AddMessage2Log($ibType->LAST_ERROR, self::MODULE_ID.' ibtype install', 0);
				}
			}
			
			$allSiteList = array();
			$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
			while($arRes = $rsSites->Fetch()){
				$allSiteList[] = $arRes["ID"];
			}
			
			$iblockCODE = "arturgolubev_ozon_changes";
			$iblockID = 0;
			
			$res = \CIBlock::GetList(Array("SORT"=>"ASC"), array('TYPE'=>$iblockTYPE, "CODE" => $iblockCODE, "CHECK_PERMISSIONS" => "N"));
			if($ar_res = $res->Fetch()){
				$iblockID = $ar_res["ID"];
				if(!COption::GetOptionString(self::MODULE_ID, 'changes_iblock_id')){
					COption::SetOptionString(self::MODULE_ID, 'changes_iblock_id', $iblockID);
				}
			}else{
				$ib = new \CIBlock;
				$arFields = Array(
					"ACTIVE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_IBLOCK_NAME"),
					"CODE" => $iblockCODE,
					"LIST_PAGE_URL" => "",
					"DETAIL_PAGE_URL" => "",
					"IBLOCK_TYPE_ID" => $iblockTYPE,
					"SITE_ID" => $allSiteList,
					"SORT" => 1900,
					"INDEX_ELEMENT" => "N",
					"INDEX_SECTION" => "N",
					"GROUP_ID" => Array()
				);
				
				$iblockID = $ib->Add($arFields);
				
				if($iblockID){
					COption::SetOptionString(self::MODULE_ID, 'changes_iblock_id', $iblockID);
				}else{
					AddMessage2Log($ib->LAST_ERROR, self::MODULE_ID.' ib install', 0);
				}
			}
			
			if($iblockID){
				$arFieldsAdd = array(
					"SID" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_SID"),
					),
					"CHANGED_ELEMENT" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_CHANGED_ELEMENT"),
					),
					"PRICE_NOW" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_PRICE_NOW"),
					),
					"PRICE_OLD" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_PRICE_OLD"),
					),
					"PRICE_MIN" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_PRICE_MIN"),
					),
				);
				
				$stores_count = COption::GetOptionString(self::MODULE_ID, $site_id.'_stores_count', 1);
				for($i = 1; $i <= $stores_count; $i++){
					$arFieldsAdd["STOCK_NOW_".$i] = array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_STOCK_NOW", array("#num#" => $i)),
					);
				}
				
				$arExistProps = self::_addIbProps($iblockID, $arFieldsAdd);
			}else{
				if(!CModule::IncludeModule("iblock")){
					$resCheck["error"] = 'add_iblock_error';
					return $resCheck;
				}
			}
			
			$iblockCODE = "arturgolubev_ozon_changes_orders";
			$iblockID = 0;
			
			$res = \CIBlock::GetList(Array("SORT"=>"ASC"), array('TYPE'=>$iblockTYPE, "CODE" => $iblockCODE, "CHECK_PERMISSIONS" => "N"));
			if($ar_res = $res->Fetch()){
				$iblockID = $ar_res["ID"];
				if(!COption::GetOptionString(self::MODULE_ID, 'changes_ord_iblock_id')){
					COption::SetOptionString(self::MODULE_ID, 'changes_ord_iblock_id', $iblockID);
				}
			}else{
				$ib = new \CIBlock;
				$arFields = Array(
					"ACTIVE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OCHANGE_IBLOCK_NAME"),
					"CODE" => $iblockCODE,
					"LIST_PAGE_URL" => "",
					"DETAIL_PAGE_URL" => "",
					"IBLOCK_TYPE_ID" => $iblockTYPE,
					"SITE_ID" => $allSiteList,
					"SORT" => 1000,
					"INDEX_ELEMENT" => "N",
					"INDEX_SECTION" => "N",
					"GROUP_ID" => Array()
				);
				
				$iblockID = $ib->Add($arFields);
				
				if($iblockID){
					COption::SetOptionString(self::MODULE_ID, 'changes_ord_iblock_id', $iblockID);
				}else{
					AddMessage2Log($ib->LAST_ERROR, self::MODULE_ID.' ib install', 0);
				}
			}

			if($iblockID){
				$arFieldsAdd = array(
					"SID" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBCHANGE_PROP_SID"),
					),
					"CHANGED_ORDER" => array(
						"TYPE" => 'S',
						"MULTIPLE" => "N",
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OCHANGE_PROP_CHANGED_ORDER"),
					),
				);
				
				$arExistProps = self::_addIbProps($iblockID, $arFieldsAdd);
			}else{
				if(!CModule::IncludeModule("iblock")){
					$resCheck["error"] = 'add_iblock_error';
					return $resCheck;
				}
			}
		}
		
		static function checkCatalogStructure($iblockID, $sid = ''){
			IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/installation.php");
			CModule::IncludeModule("iblock");
			
			$arFieldsAdd = array(
				"OZ_AUTOSALES" => array(
					"TYPE" => 'L',
					"ENUM_LIST" => array(
						Array(
							"VALUE" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_YES"),
							"XML_ID" => "ENABLED",
							"SORT" => "20"
						),
						Array(
							"VALUE" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_AUTOSALES_NO"),
							"XML_ID" => "DISABLED",
							"SORT" => "30"
						),
						Array(
							"VALUE" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_AUTOSALES_UNK"),
							"XML_ID" => "UNKNOWN",
							"SORT" => "40"
						),
					),
					"MULTIPLE" => "N",
					"FILTRABLE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_AUTOSALES"),
				),
				"OZ_ARTICLE" => array(
					"TYPE" => 'S',
					"MULTIPLE" => "N",
					"FILTRABLE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_ARTICLE"),
				),
				"OZ_EXPORT" => array(
					"TYPE" => 'L',
					"ENUM_LIST" => array(
						Array(
							"VALUE" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_YES"),
							"XML_ID" => "Y",
							"SORT" => "10"
						),
					),
					"MULTIPLE" => "N",
					"FILTRABLE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_EXPORT"),
				),
			);
			
			if($sid){
				$arFieldsAdd['OZ_SKU_'. strtoupper($sid)] = array(
					"TYPE" => 'S',
					"MULTIPLE" => "N",
					"FILTRABLE" => "Y",
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_IBPROP_SKU", ["#SID#" => $sid]),
				);
			}
			
			$arExistProps = self::_addIbProps($iblockID, $arFieldsAdd);
		}
		
		static function _addIbProps($iblockID, $arFieldsAdd){
			$ibp = new CIBlockProperty;
				
			$arExistProps = array();
			$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$iblockID));
			while ($prop_fields = $properties->GetNext()){
				$arExistProps[$prop_fields["CODE"]] = $prop_fields["ID"];
			}
			
			$SORT = 800;
			foreach($arFieldsAdd as $k=>$arField){
				$SORT += 10;
				
				if($arExistProps[$k]) continue;
				
				$arFields = array(
					"NAME" => $arField["NAME"],
					"MULTIPLE" => $arField["MULTIPLE"],
					"ACTIVE" => "Y",
					"SORT" => $SORT,
					"CODE" => $k,
					"FILTRABLE" => ($arField["FILTRABLE"]) ? $arField["FILTRABLE"] : "N",
					"PROPERTY_TYPE" => $arField["TYPE"],
					"IBLOCK_ID" => $iblockID
				);
				
				if($arField["ENUM_LIST"]){
					$arFields["VALUES"] = $arField["ENUM_LIST"];
				}
				
				$PropID = $ibp->Add($arFields);
				
				$arExistProps[$k] = $PropID;
			}
			
			return $arExistProps;
		}
		
		static function IncludeAdminFile($m, $p){
			global $APPLICATION, $DOCUMENT_ROOT;
			$APPLICATION->IncludeAdminFile($m, $DOCUMENT_ROOT.$p);
		}
		
		static function addGadgetToDesctop($gadget_id){
			if(!defined("NO_INSTALL_MWATCHER") && class_exists('CUserOptions')){
				$desctops = \CUserOptions::GetOption('intranet', '~gadgets_admin_index', false, false);
				if(is_array($desctops) && !empty($desctops[0])){
					$skip = 0;
					foreach($desctops[0]['GADGETS'] as $gid => $gsett){
						if(strstr($gid, $gadget_id)) $skip = 1;
					}
					
					if(!$skip){
						foreach($desctops[0]['GADGETS'] as $gid => $gsett){
							if($gsett['COLUMN'] == 0){
								$desctops[0]['GADGETS'][$gid]['ROW']++;
							}
						}
						
						$gid_new = $gadget_id."@".rand();
						$desctops[0]['GADGETS'][$gid_new] = array('COLUMN' => 0, 'ROW' => 0);
						
						\CUserOptions::SetOption('intranet', '~gadgets_admin_index', $desctops, false, false);
					}
				}
			}
		}
	
		static function checkOrderStructure($pid){
			if(!$pid || !CModule::IncludeModule("sale")) return 0;
			
			$db_propsGroup = CSaleOrderPropsGroup::GetList(
				array("SORT" => "ASC"),
				array("PERSON_TYPE_ID" => $pid, "NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OGPROP_OZ_NAME")),
				false, false, array()
			);
			if($propsGroup = $db_propsGroup->Fetch()){
				$pgid = $propsGroup["ID"];
			}else{
				$pgid = CSaleOrderPropsGroup::Add(array(
					"PERSON_TYPE_ID" => $pid,
					"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OGPROP_OZ_NAME"),
					"SORT" => 100,
				));
			}
			if($pgid){
				$arFieldsAdd = array(
					"AOZ_POSTING_NUMBER" => array(
						"TYPE" => 'TEXT',
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_POSTING_NUMBER"),
					),
					// "AOZ_TRACKING_NUMBER" => array(
						// "TYPE" => 'TEXT',
						// "NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_TRACKING_NUMBER"),
					// ),
					"AOZ_STATUS" => array(
						"TYPE" => 'TEXT',
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_STATUS"),
					),
					"AOZ_SHIPMENT_DATA" => array(
						"TYPE" => 'TEXT',
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_SHIPMENT_DATA"),
					),
					"AOZ_WAREHOUSE_ID" => array(
						"TYPE" => 'TEXT',
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_WAREHOUSE_ID"),
					),
					"AOZ_SID" => array(
						"TYPE" => 'TEXT',
						"NAME" => GetMessage("ARTURGOLUBEV_OZON_INSTALL_OPROP_SID"),
					),
				);
				// echo '<pre>'; print_r($arFieldsAdd); echo '</pre>';
				// return 0;
				
				$arExistProps = array();
				$db_props = \CSaleOrderProps::GetList(array("SORT" => "ASC"), array("PERSON_TYPE_ID" => $pid), false, false, array());
				while ($props = $db_props->Fetch()){
					$arExistProps[$props["CODE"]] = $props["ID"];
				}
				
				$SORT = 800;
				foreach($arFieldsAdd as $k=>$arField){
					$SORT += 10;
					
					if($arExistProps[$k]) continue;
					
					$arField["SORT"] = $SORT;
					$arField["CODE"] = $k;
					$arField["PERSON_TYPE_ID"] = $pid;
					$arField["PROPS_GROUP_ID"] = $pgid;
					$arField["REQUIED"] = "N";
					$arField["UTIL"] = "Y";
					
					$arExistProps[$k] = CSaleOrderProps::Add($arField);
					// echo '<pre>'; print_r($arField); echo '</pre>';
				}
				
				// echo 'arExistProps <pre>'; print_r($arExistProps); echo '</pre>';
			}
		}
	
	}
}