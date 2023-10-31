<?
///////От взлома
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{          
    header("Status: 404 Not Found");
    die();
}
///////
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/tools/uf.php");?>