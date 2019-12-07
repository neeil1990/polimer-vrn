<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

//-----------------------------------------------------------------------------------------------------------------------------------------------
Loc::loadMessages(__FILE__);

if(file_exists(dirname(__FILE__).'/entities/'.htmlspecialcharsEx($_POST['entity']).'.php') && Loader::includeModule('sotbit.schemaorg')) {
    ob_start();
    require_once(dirname(__FILE__).'/entities/'.htmlspecialcharsEx($_POST['entity']).'.php');
    $html = ob_get_contents();
    ob_clean();
}
exit(json_encode(array('html'=> iconv(LANG_CHARSET, 'UTF-8', $html))));
?>