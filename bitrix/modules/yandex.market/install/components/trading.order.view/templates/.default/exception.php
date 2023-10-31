<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

\CAdminMessage::ShowMessage([
	'TYPE' => 'ERROR',
	'MESSAGE' => $arResult['ERROR'],
]);