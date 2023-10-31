<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

$date = date('ymd');

if (Market\Config::getOption('delivery_act_counter_date') === $date)
{
	$previousCounter = max(1, (int)Market\Config::getOption('delivery_act_counter', 1));
	$counter = $previousCounter + 1;
}
else
{
	Market\Config::setOption('delivery_act_counter_date', $date);
	$counter = 1;
}

Market\Config::setOption('delivery_act_counter', $counter);

$counterMinLength = 2;
$counterFilled = $counter;
$counterNeedSymbolsCount = $counterMinLength - Market\Data\TextString::getLength($counter);

if ($counterNeedSymbolsCount > 0)
{
	$counterFilled = str_repeat('0', $counterNeedSymbolsCount) . $counter;
}

$arResult['DOCUMENT_NUMBER'] = $date . $counterFilled;