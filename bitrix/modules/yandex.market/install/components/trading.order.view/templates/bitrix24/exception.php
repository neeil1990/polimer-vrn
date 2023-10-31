<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;

Main\UI\Extension::load('ui.alerts');

?>
<div class="ui-alert ui-alert-danger">
	<?= $arResult['ERROR'] ?>
</div>
