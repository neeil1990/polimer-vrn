<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $adminList \CAdminSubList */

ob_start();
$adminList->DisplayList();
$contents = ob_get_clean();

$contents = preg_replace('/function (ReloadSubList|ReloadOffers)\(\)\s*{.*?}/s', '', $contents);

echo $contents;