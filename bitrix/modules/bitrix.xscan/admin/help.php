<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

if (!$USER->IsAdmin())
    $APPLICATION->AuthForm();

IncludeModuleLangFile(__FILE__);

if (function_exists('mb_internal_encoding'))
    mb_internal_encoding('ISO-8859-1');


$strError = '';
$file = '';

$APPLICATION->SetTitle(GetMessage("BITRIX_XSCAN_HELP"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");


if (!is_dir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/bitrix.xscan")) {
    $x = CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/bitrix.xscan/install/images", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/bitrix.xscan", false, true);
}


?>

<div>
    <?= GetMessage("BITRIX_XSCAN_HELLO") ?>
</div>

<style>
    .xscan-code{
        background-color: #fff;
        padding: 10px;
        word-break: break-all;
        max-width: 1000px;
        white-space: pre-wrap;
    }

    .xscan-img {
        max-width: 1200px;
        margin-top: 30px;
        border-radius: 8px;
    }


    .adm-info-message > p, li {
        font-size: 14px;
    }
</style>

<?php

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin_after.php");
?>
