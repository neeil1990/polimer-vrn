<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main;

$arResult['FORMAT_DATA'] = [];

if (
	!empty($arResult['ITEM']['EXPORT_SERVICE'])
	&& !empty($arResult['ITEM']['EXPORT_FORMAT'])
	&& Main\Loader::includeModule('yandex.market')
)
{
	try
	{
		$format = Market\Export\Xml\Format\Manager::getEntity(
			$arResult['ITEM']['EXPORT_SERVICE'],
			$arResult['ITEM']['EXPORT_FORMAT']
		);

		$arResult['FORMAT_DATA']['SUPPORT_DELIVERY_OPTIONS'] = $format->isSupportDeliveryOptions();
	}
	catch (Main\SystemException $exception)
	{
		// silent
	}
}