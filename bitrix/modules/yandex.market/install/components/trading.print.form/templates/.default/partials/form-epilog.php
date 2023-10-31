<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */

if (!empty($arResult['DOCUMENT_DESCRIPTION']))
{
	echo BeginNote();
	echo $arResult['DOCUMENT_DESCRIPTION'];
	echo EndNote();
}
?>
</form>
