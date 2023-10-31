<?
use Bitrix\Main\Context,
    Bitrix\Main\Loader;

global $APPLICATION;
IncludeModuleLangFile(__FILE__);
$APPLICATION->SetTitle(GetMessage('SBL_SETTINGS_TITLE_ONE'));
LocalRedirect("/bitrix/admin/maxyss.ozon_ozon_maxyss_general.php?lang=".SITE_ID);