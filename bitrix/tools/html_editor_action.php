<?
///////От взлома
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{          
    header("Status: 404 Not Found");
    die();
}
///////
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/fileman_html_editor_action.php");
?>