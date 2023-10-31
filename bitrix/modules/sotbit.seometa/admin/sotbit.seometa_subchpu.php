<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sotbit.seometa/prolog.php");
IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();
/*
 * this page only for actions and get info
 *
 */
define('B_ADMIN_SUBCHPU',1);
define('B_ADMIN_SUBCHPU_LIST',true);

global $APPLICATION;
global $USER;

$moduleId = "sotbit.seometa";

CModule::IncludeModule($moduleId);
                
$strSubElementAjaxPath = '/bitrix/admin/seometa_subchpu_admin.php?lang='.LANGUAGE_ID.'&ID='.intval($_REQUEST['ID']);
require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sotbit.seometa/admin/templates/sub_chpu.php');
              
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>