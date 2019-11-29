<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sotbit.seometa/prolog.php");
IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();
/*
 * this page only for actions and get info
 *
 */
define('B_ADMIN_SUBCONDITIONS',1);
define('B_ADMIN_SUBCONDITIONS_LIST',true);

global $APPLICATION;
global $USER;
         
CModule::IncludeModule("sotbit.seometa");                                       
                
$strSubElementAjaxPath = '/bitrix/admin/seometa_suburl_admin.php?lang='.LANGUAGE_ID.'&ID='.intval($_REQUEST['ID']);
require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sotbit.seometa/admin/templates/sub_list.php');
              
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>