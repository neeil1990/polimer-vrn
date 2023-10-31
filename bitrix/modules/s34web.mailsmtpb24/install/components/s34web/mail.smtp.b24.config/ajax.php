<?php
/**
 * Created: 24.03.2021, 18:36
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');
require_once(dirname(__FILE__).'/class.php');

$report = new mailSMTPB24Config();
$report->executeComponentAjax();

CMain::FinalActions();
die();