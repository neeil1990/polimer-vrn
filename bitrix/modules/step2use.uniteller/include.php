<?php
/**
 * ���� ������������ ��� ����������� ������ �� ����� ���������� �������� �����.
 * � ��� ������ ���������� ��������� ���� ������ � ������������ ������� � ������� ������.
 * @author r.smoliarenko
 * @author r.sarazhyn
 */

IncludeModuleLangFile(__FILE__);

CModule::AddAutoloadClasses(
	'step2use.uniteller',
	array(
		'CUnitellerAgentRemLog' => 'classes/general/uniteller_agent_log.php',
	)
);

/**
 * ����� ��� ������ UnitellerAgent();.
 * ����� ������ ����������� �� �����, ����� �� ����� �������� ��������� �������������.
 * @author r.smoliarenko
 * @author r.sarazhyn
 */
Class CUnitellerAgentRem {
	/**
	 * ������� ������.
	 * �������� ������ ���� ������� �������� ������� Uniteller � ��� ��� �� ���, ������ ������� ��� ����������,
	 * �������� ������������� �������� ������� �� ��������� ��������������� ��������.
	 * � �������� ������������� �������� �������-����� ������ ������� PHP ��� ������� ����� ����������� ��� ��������� ������� ������ �������.
	 * @return string
	 */
	function UnitellerAgent() {
		set_time_limit(60 * 20);

		if (file_exists($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/step2use.uniteller/payment/step2use.uniteller/tools.php')) {
			if (!class_exists('ps_uniteller')) {
				include($_SERVER['DOCUMENT_ROOT'] . BX_ROOT .  '/modules/step2use.uniteller/payment/step2use.uniteller/tools.php');
			}
		} else {
			return 'CUnitellerAgentRem::UnitellerAgent();';
		}

		// ������� ����, ��� ������ �������� ������ ����.
		if (!defined('UNITELLER_AGENT') || UNITELLER_AGENT !== true) {
			return 'CUnitellerAgentRem::UnitellerAgent();';
		}

		CModule::IncludeModule('sale');
		// ���������� ID ����������� �������� ������� Uniteller
		$uniteller_payment_id = -1;
		$dbPaySystem = CSalePaySystem::GetList();
		while ($arPaySystem = $dbPaySystem->Fetch()) {
			if (strtolower($arPaySystem['NAME']) == 'uniteller') {
				$uniteller_payment_id = (int)$arPaySystem['ID'];
			}
		}
		if ($uniteller_payment_id == -1) {
			return 'CUnitellerAgentRem::UnitellerAgent();';
		}

		// � �������-������ �� �������� ���������� ������ $USER ������ CUser.
		global $USER;
		if (!is_object($USER)) {
			$USER = new CUser;
		}
		// � CSaleOrder::GetList ������������ ���������� ������ $USER ������ CUser.
		$db_sales = CSaleOrder::GetList(false, array('PAY_SYSTEM_ID' => $uniteller_payment_id));

		while ($arOrder = $db_sales->Fetch()) {
			$arOrder = CSaleOrder::GetByID($arOrder['ID']);
			ps_uniteller::setMerchantData($arOrder['ID']);
			$uniteller_sync_time = strtotime($arOrder['PS_RESPONSE_DATE']);
			if ($uniteller_sync_time >= ps_uniteller::$date_fix_order_sync) {
				ps_uniteller::doSyncStatus($arOrder);
			}
		}

		return 'CUnitellerAgentRem::UnitellerAgent();';
	}
}

class CStepUseUniteller {
    public static function onEpilog(){//����������� �����
		if(
			(
				strpos($_SERVER['PHP_SELF'], "/bitrix/admin/sale_order_detail.php")!==false || 
				strpos($_SERVER['PHP_SELF'], "/bitrix/admin/sale_order_view.php")!==false
			) && 
			cmodule::includeModule('sale')
		) {
            include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/step2use.uniteller/admin/uniteller_include_order_detail.php");
        }
		//	return false;
	}
}
?>