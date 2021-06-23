<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $component \Yandex\Market\Components\AdminGridList */

$disabledRows = [];

foreach ($component->getViewList()->aRows as $row)
{
	if ($row->bReadOnly)
	{
		$disabledRows[] = $row->id;
	}
}

if (!empty($disabledRows))
{
	$arResult['LIST_EXTENSION']['disabledRows'] = $disabledRows;
}