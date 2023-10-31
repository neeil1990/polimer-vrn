<?php
IncludeModuleLangFile(__FILE__);

class CASDTplVars {

	public static function OnBeforeEndBufferContent() {
		if (isset($GLOBALS['APPLICATION']->arPanelButtons) &&
			is_array($GLOBALS['APPLICATION']->arPanelButtons) &&
			!empty($GLOBALS['APPLICATION']->arPanelButtons)
		) {
			if ($GLOBALS['USER']->CanDoOperation('lpa_template_edit')) {
				$arPanelButtons = &$GLOBALS['APPLICATION']->arPanelButtons;
				foreach ($arPanelButtons as &$arItemPanel) {
					if ($arItemPanel['ICON'] == 'bx-panel-site-template-icon') {
						if (isset($arItemPanel['MENU']) && is_array($arItemPanel['MENU'])) {
							$popupAction = "javascript:(new BX.CDialog({
											width: 550,
											height: 300,
											resizable: false,
											buttons: [BX.CDialog.btnSave, BX.CDialog.btnCancel],
											content_url: '/bitrix/tools/asd.tplvars/edit_vars.php?bxpublic=Y&site=".SITE_ID."'})).Show();";
							$arItemPanel['MENU'][] = array(
								'TEXT' => GetMessage('ASD_TPLVARS_PANEL_TITLE'),
								'ACTION' => $popupAction,
							);
						}
					}
				}
				unset($arItemPanel);
			}
		}
	}

	public static function GetOptionsDesc($code, $site=SITE_ID) {
		return CASDOption::GetOptionsDesc($code, $site);
	}
}

if (version_compare(SM_VERSION, '14.0.0')>=0) {
	class CASDOption extends \Bitrix\Main\Config\Option {
		public static function GetOptions() {
			self::load('tpl_vars');
			return self::$options;
		}

		public static function GetOptionsDesc($code, $site = SITE_ID) {
			static $optionList = array();
			if (empty($optionList)) {
				$optionList['asd_null'] = null;
				$optionIterator = \Asd\Tplvars\OptionDescrTable::getList(array(
					'select' => array('NAME', 'DESCRIPTION'),
					'filter' => array('=SITE_ID' => $site)
				));
				while ($option = $optionIterator->fetch()) {
					$optionList[$option['NAME']] = $option['DESCRIPTION'];
				}
				unset($option, $optionIterator);
			}
			if (!isset($optionList[$code])) {
				$conn = \Bitrix\Main\Application::getConnection();;
				$helper = $conn->getSqlHelper();
				$descrIterator = $conn->query(
					'select DESCRIPTION from '.$helper->quote('b_option').' where '.
					$helper->quote('MODULE_ID').' = \'tpl_vars\' and '.
					$helper->quote('NAME').' = \''.$helper->forSql($code).'\' and '.
					$helper->quote('SITE_ID').' = \''.$helper->forSql($site).'\''
				);
				if ($descr = $descrIterator->fetch()) {
					$descr['DESCRIPTION'] = (string)$descr['DESCRIPTION'];
					if ($descr['DESCRIPTION'] != '') {
						$optionIterator = \Asd\Tplvars\OptionDescrTable::getList(array(
							'select' => array('ID'),
							'filter' => array('=SITE_ID' => $site, '=NAME' => $code)
						));
						if ($option = $optionIterator->fetch()) {
							$res = \Asd\Tplvars\OptionDescrTable::update($option['ID'], array('DESCRIPTION' => $descr['DESCRIPTION']));
							if ($res->isSuccess(true)) {
								$optionList[$code] = $descr['DESCRIPTION'];
							}
							unset($res);
						} else {
							$fileds = array(
								'SITE_ID' => $site,
								'NAME' => $code,
								'DESCRIPTION' => $descr['DESCRIPTION']
							);
							$res = \Asd\Tplvars\OptionDescrTable::add($fileds);
							if ($res->isSuccess(true)) {
								$optionList[$code] = $descr['DESCRIPTION'];
							}
							unset($res, $fields);
						}
						unset($option, $optionIterator);
					}
				}
				unset($descr, $descrIterator, $helper, $conn);
			}
			return (isset($optionList[$code]) ? $optionList[$code] : '');
		}

		public static function SetOption($name, $value, $desc, $site = SITE_ID) {
			$site = (string)$site;
			if ($site == '')
				$site = SITE_ID;
			self::set('tpl_vars', $name, $value, $site);
			$optionIterator = \Asd\Tplvars\OptionDescrTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=NAME' => $name, '=SITE_ID' => $site)
			));
			if ($desc === false) {
				$desc = null;
			}
			if ($option = $optionIterator->fetch()) {
				$fields = array('DESCRIPTION' => $desc);
				$result = \Asd\Tplvars\OptionDescrTable::update($option['ID'], $fields);
			} else {
				$fields = array('NAME' => $name, 'DESCRIPTION' => $desc, 'SITE_ID' => $site);
				$result = \Asd\Tplvars\OptionDescrTable::add($fields);
			}

			unset($fields);
		}

		public static function RemoveOption($name, $site = SITE_ID) {
			$filter = array(
				'name' => $name
			);
			if (strlen($site) > 0)
				$filter['site_id'] = $site;
			self::delete('tpl_vars', $filter);
			$filter = array(
				'=NAME' => $name
			);
			if (strlen($site) > 0)
				$filter['=SITE_ID'] = $site;
			$optionIterator = \Asd\Tplvars\OptionDescrTable::getList(array(
				'select' => array('ID'),
				'filter' => $filter
			));
			while ($option = $optionIterator->fetch()) {
				$result = \Asd\Tplvars\OptionDescrTable::delete($option['ID']);
			}
		}
	}
} else {
	class CASDOption {
		public static function GetOptions() {
			global $MAIN_OPTIONS;
			return $MAIN_OPTIONS;
		}

		public static function GetOptionsDesc($code, $site=SITE_ID) {
			static $arOptions = array();
			if (empty($arOptions)) {
				$arOptions['asd_null'] = null;
				$site = $GLOBALS['DB']->ForSQL($site, 2);
				$rsOpt = $GLOBALS['DB']->Query("select NAME, DESCRIPTION from b_asd_option_descr where SITE_ID='".$site."'");
				while ($arOpt = $rsOpt->Fetch()) {
					$arOptions[$arOpt['NAME']] = $arOpt['DESCRIPTION'];
				}
				unset($arOpt, $rsOpt);
			}
			if (!isset($optionList[$code])) {
				$descrIterator = $GLOBALS['DB']->Query(
					"select DESCRIPTION from b_option where MODULE_ID = 'tpl_vars' and SITE_ID='".$site."' and NAME = '".$GLOBALS['DB']->ForSQL($code)."'"
				);
				if ($descr = $descrIterator->Fetch()) {
					$descr['DESCRIPTION'] = (string)$descr['DESCRIPTION'];
					if ($descr['DESCRIPTION'] != '') {
						$optionIterator = $GLOBALS['DB']->Query(
							"select ID from b_asd_option_descr where SITE_ID='".$site."' and NAME = '".$GLOBALS['DB']->ForSQL($code)."'"
						);
						if ($option = $optionIterator->Fetch()) {
							$fields = array('DESCRIPTION' => $descr['DESCRIPTION']);
							$update = $GLOBALS['DB']->PrepareUpdate('b_asd_option_descr', $fields);
							if (!empty($update)) {
								$res = $GLOBALS['DB']->Query(
									"update b_asd_option_descr set ".$update." where ID = ".$option['ID']
								);
								$optionList[$code] = $descr['DESCRIPTION'];
							}
						} else {
							$fields = array(
								'SITE_ID' => $site,
								'NAME' => $code,
								'DESCRIPTION' => $descr['DESCRIPTION']
							);
							$insert = $GLOBALS['DB']->PrepareInsert('b_asd_option_descr', $fields);
							$GLOBALS['DB']->Query(
								"insert into b_asd_option_descr(".$insert[0].") values(".$insert[1].")"
							);
						}
						unset($option, $optionIterator);
					}
				}
				unset($descr, $descrIterator);
			}
			return (isset($arOptions[$code]) ? $arOptions[$code] : '');
		}

		public static function SetOption($name, $value, $desc, $site = SITE_ID) {
			COption::SetOptionString('tpl_vars', $name, $value, $desc, $site);
		}

		public static function RemoveOption($name, $site = SITE_ID) {
			COption::RemoveOption('tpl_vars', $name, $site);
		}
	}
}