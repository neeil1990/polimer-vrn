<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

global $APPLICATION;
global $USER;
global $GROUPS;
global $RIGHTS;
global $SITES;

$module_id = Market\Config::getModuleName();
require $_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/main/admin/group_rights.php';