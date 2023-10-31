<?php
/**
 * Файл подключается при подключении модуля во время выполнения скриптов сайта.
 * В нем должны находиться включения всех файлов с библиотеками функций и классов модуля.
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
 * Класс для агента UnitellerAgent();.
 * Агент должен запускаться по крону, чтобы не мешал работать остальных пользователей.
 * @author r.smoliarenko
 * @author r.sarazhyn
 */
Class CUnitellerAgentRem {
	/**
	 * Функция агента.
	 * Получает список всех заказов платёжной системы Uniteller и для тех из них, статус которых мог измениться,
	 * проводит синхронизацию статусов заказов со статусами соответствующах платежей.
	 * В качестве возвращаемого значения функция-агент должна вернуть PHP код который будет использован при следующем запуске данной функции.
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

		// Признак того, что агента запустил именно крон.
		if (!defined('UNITELLER_AGENT') || UNITELLER_AGENT !== true) {
			return 'CUnitellerAgentRem::UnitellerAgent();';
		}

		CModule::IncludeModule('sale');
		// Определяет ID обработчика платёжной системы Uniteller
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

		// В функции-агенте не доступен глобальный объект $USER класса CUser.
		global $USER;
		if (!is_object($USER)) {
			$USER = new CUser;
		}
		// В CSaleOrder::GetList используется глобальный объект $USER класса CUser.
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
    public static function onEpilog(){//Отображение формы
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