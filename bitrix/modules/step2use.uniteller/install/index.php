<?php
/**
 * Обязательный файл с описанием модуля, содержащий инсталлятор/деинсталлятор модуля.
 * @author Атлант mp@atlant2010.ru
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/prolog.php'); // пролог модуля

/**
 * Класс для инсталляции и деинсталляции модуля step2use.uniteller.
 * @author Атлант mp@atlant2010.ru
 *
 */
class step2use_uniteller extends CModule {
	// Обязательные свойства.
	/**
	 * Имя партнера - автора модуля.
	 * @var string
	 */
	const MODULE_ID = 'step2use.uniteller';
	var $PARTNER_NAME;
	/**
	 * URL партнера - автора модуля.
	 * @var string
	 */
	var $PARTNER_URI;
	/**
	 * Версия модуля.
	 * @var string
	 */
	var $MODULE_VERSION;
	/**
	 * Дата и время создания модуля.
	 * @var string
	 */
	var $MODULE_VERSION_DATE;
	/**
	 * Имя модуля.
	 * @var string
	 */
	var $MODULE_NAME;
	/**
	 * Описание модуля.
	 * @var string
	 */
	var $MODULE_DESCRIPTION;
	/**
	 * Массив с путями для инсталляции модуля.
	 * @var array
	 */
	var $aPaths;
	/**
	 * ID модуля. 
	 * @var string
	 */
	var $MODULE_ID = 'step2use.uniteller';

	/**
	 * Конструктор класса. Задаёт начальные значения свойствам.
	 */
	function step2use_uniteller() {
		$this->MODULE_ID = "step2use.uniteller";
		$this->PARTNER_NAME = GetMessage("ATL_U_MODULE_PARTNER");
		$this->PARTNER_URI = "https://atlant2010.ru";

		$arModuleVersion = array();

		$path = str_replace('\\', '/', __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($path . '/version.php');

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = GetMessage('UNITELLER.SALE_INSTALL_NAME');
		if (CModule::IncludeModule($this->MODULE_ID)) {
			$this->MODULE_DESCRIPTION = GetMessage('UNITELLER.SALE_INSTALL_DESCRIPTION');
		} else {
			$this->MODULE_DESCRIPTION = GetMessage('UNITELLER.SALE_PREINSTALL_DESCRIPTION');
		}
		$this->aPaths = array(
			'admin' => '/bitrix/admin',
			'components' => '/bitrix/components',
			'php_interface' => '/bitrix/php_interface',
			'templates' => '/bitrix/templates',
			'personal' => '/personal/ordercheck/result_rec.php',
			'cron.bat' => '',
		);
	}

	/**
	 * Устанавливает модуль.
	 */
	function DoInstall() {
		global $APPLICATION, $DB;

		$GLOBALS['errors'] = false;
		$this->errors = false;
        
        $this->InstallDB();

		// Создаёт таблицу в БД.
		$DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/db/mysql/install.sql');

		// Копирует нужные файлы в нужные места.
		if (!CModule::IncludeModule($this->MODULE_ID)) {
			$this->InstallFiles();
			RegisterModule($this->MODULE_ID);

			// Создаёт агента
			//CAgent::AddAgent('CUnitellerAgent::UnitellerAgent();', $this->MODULE_ID, 'Y', 60, '', 'Y', '', 0);
		}

		$GLOBALS['errors'] = $this->errors;

		// Показывает страницу с результатом установки модуля.
		$APPLICATION->IncludeAdminFile(GetMessage('UNITELLER.SALE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/step_ok.php');
	}

	/**
	 * Удаляет модуль.
	 */
	function DoUninstall() {
		global $APPLICATION, $uninstall;

		if (isset($uninstall) && $uninstall == 'Y' && CModule::IncludeModule($this->MODULE_ID)) {
			$this->UnInstallFiles();

			// Удаляет агента
			//CAgent::RemoveAgent('CUnitellerAgent::UnitellerAgent();', $this->MODULE_ID);
			UnRegisterModule($this->MODULE_ID);

			// Удаляет таблицу из БД, если пользователь решил удалить её.
			$this->UnInstallDB(array(
				'savedata' => $_REQUEST['savedata'],
			));
		} else {
			// Показывает страницу с настройками удаления модуля.
			$APPLICATION->IncludeAdminFile(GetMessage('UNITELLER.SALE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/unstep_ok.php');
		}
	}

	/**
	 * Копирует файлы модуля в нужные места.
	 * @return boolean
	 */
	function InstallFiles() {
		$path_from = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/www';
		$path_to = $_SERVER['DOCUMENT_ROOT'];

		/*if (!CopyDirFiles($path_from . $this->aPaths['admin'], $path_to . $this->aPaths['admin'], true, true, false, '.svn')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}*/
		/*if (!CopyDirFiles($path_from . $this->aPaths['components'], $path_to . $this->aPaths['components'], true, true, false, '.svn')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}*/
		if (!CopyDirFiles($path_from . $this->aPaths['php_interface'], $path_to . $this->aPaths['php_interface'], true, true, false, '.svn')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}
		/*if (!CopyDirFiles($path_from . $this->aPaths['templates'], $path_to . $this->aPaths['templates'], true, true, false, '.svn')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}*/
		if (!CopyDirFiles($path_from . $this->aPaths['personal'], $path_to . $this->aPaths['personal'], true, true, false, '.svn')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}
		/*if (!CopyDirFiles($path_from . $this->aPaths['cron.bat'] . '/cron.bat', $path_to . $this->aPaths['cron.bat'] . '/cron.bat')) {
			$this->errors = array(GetMessage('UNITELLER.SALE_INSTALL_ERROR'));
		}*/

		return true;
	}

	/**
	 * Удаляет файлы модуля отовсюду.
	 * @return boolean
	 */
	function UnInstallFiles() {
		$path_from = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/www';
		$path_to = $_SERVER['DOCUMENT_ROOT'];

		//DeleteDirFiles($path_from . $this->aPaths['admin'], $path_to . $this->aPaths['admin'], array('.svn'));

		//DeleteDirFilesEx('/bitrix/components/bitrix/sale.personal.ordercheck');
		//DeleteDirFilesEx('/bitrix/components/bitrix/sale.personal.ordercheck.cancel');
		//DeleteDirFilesEx('/bitrix/components/bitrix/sale.personal.ordercheck.check');
		//DeleteDirFilesEx('/bitrix/components/bitrix/sale.personal.ordercheck.detail');
		//DeleteDirFilesEx('/bitrix/components/bitrix/sale.personal.ordercheck.list');

		DeleteDirFilesEx('/bitrix/php_interface/include/sale_payment/step2use.uniteller');

		//DeleteDirFilesEx('/bitrix/templates/.default/components/bitrix/sale.personal.ordercheck.cancel');
		//DeleteDirFilesEx('/bitrix/templates/.default/components/bitrix/sale.personal.ordercheck.check');
		//DeleteDirFilesEx('/bitrix/templates/.default/components/bitrix/sale.personal.ordercheck.detail');
		//DeleteDirFilesEx('/bitrix/templates/.default/components/bitrix/sale.personal.ordercheck.list');

		//DeleteDirFilesEx('/personal/ordercheck');

		//DeleteDirFiles($path_from . $this->aPaths['cron.bat'], $path_to . $this->aPaths['cron.bat'], array('bitrix', 'personal', '.svn'));

		return true;
	}

	/**
	 * Удаляет таблицу из БД.
	 * @return boolean
	 */
	function UnInstallDB($arParams = Array()) {
		if (array_key_exists('savedata', $arParams) && $arParams['savedata'] != 'Y') {
			global $DB;
			//$DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/install/db/' . strtolower($DB->type) . '/uninstall.sql');
		}
        
        UnRegisterModuleDependences("main", "OnEpilog", self::MODULE_ID, "CStepUseUniteller", "onEpilog");

		return true;
	}
    
    /**
     * Этот метод нужен, чтобы корректно работал ДЕМО-период
     * @see https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=101&LESSON_ID=3217&LESSON_PATH=8781.4793.3217
     */
    public function InstallDB() {
        
        RegisterModuleDependences("main", "OnEpilog", self::MODULE_ID, "CStepUseUniteller", "onEpilog");
        
        return true;
    }
}

?>