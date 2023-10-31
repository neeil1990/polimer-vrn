<?
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PERFMON_STOP', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$moduleCode = 'arturgolubev.ecommerce';

if(COption::GetOptionString($moduleCode, 'off_mode', 'N') == 'Y')
{
	echo 'document.location.reload(true);';
	return;
}

if(CModule::IncludeModule($moduleCode))
{
	$scripts = CArturgolubevEcommerce::checkReadyEvents();
	echo $scripts;
}
?>