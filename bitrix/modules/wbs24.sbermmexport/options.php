<?
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

if (!$USER->IsAdmin()) return;

$moduleId = basename(__DIR__);
$suffix = strtoupper($moduleId);

Loc::loadMessages(__FILE__);
Loader::includeModule($moduleId);

$request = Application::getInstance()->getContext()->getRequest();
$uriString = $request->getRequestUri();
$uri = new Uri($uriString);
$redirect = $uri->getUri();

$aTabs = [
	[
		"DIV" => str_replace(".", "_", $moduleId),
		"TAB" => Loc::getMessage($suffix.".SETTINGS"),
		"ICON" => "settings",
		"TITLE" => Loc::getMessage($suffix.".TITLE"),
	],
];
$arAllOptions = [
	"main" => [
		['note' => Loc::getMessage($suffix.".NOTE")],
	],
];

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"])) && check_bitrix_sessid()) {
	__AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
	LocalRedirect($redirect);
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<form method="post" action="<?=$redirect?>" name="<?=str_replace(".", "_", $moduleId)?>">
	<?
	echo bitrix_sessid_post();

	$tabControl->Begin();

	$tabControl->BeginNextTab();

	__AdmSettingsDrawList($moduleId, $arAllOptions["main"]);

	//$tabControl->Buttons([]); // отключено, т.к. пока нет свойств
	$tabControl->End();
	?>
</form>
