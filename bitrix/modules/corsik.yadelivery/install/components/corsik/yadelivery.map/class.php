<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Options;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
Loader::includeModule('corsik.yadelivery');

class YadeliveryMap extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->getDeliveryOptions();
		$this->includeComponentTemplate();
	}

	public function getDeliveryOptions()
	{
		Handler::sale_ComponentOrderOneStepProcess();
		$this->arResult['CURRENCY'] = Options::getOptionByName("currency");
		$this->arResult['YANDEX_MAPS_API_KEY'] = htmlspecialcharsbx(Main\Config\Option::get('fileman', 'yandex_map_api_key', ''));
	}
}
