<?
if ( file_exists( $_SERVER["DOCUMENT_ROOT"]."/local/modules/askaron.pro1c/admin/askaron_pro1c_last.php" ) )
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/askaron.pro1c/admin/askaron_pro1c_last.php");
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/askaron.pro1c/admin/askaron_pro1c_last.php");	
}
?>