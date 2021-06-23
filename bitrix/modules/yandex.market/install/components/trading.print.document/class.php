<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Yandex\Market;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

class TradingPrintDocument extends \CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult['ITEMS'] = (array)$this->arParams['ITEMS'];

		$this->includeComponentTemplate();
	}
}