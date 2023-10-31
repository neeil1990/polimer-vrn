<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;

class Delivery extends Market\Trading\Service\Marketplace\Delivery
{
	const SHOP_SERVICE_ID = 99;

	const DISPATCH_TYPE_BUYER = 'BUYER';
	const DISPATCH_TYPE_MARKET_BRANDED_OUTLET = 'MARKET_BRANDED_OUTLET';
	const DISPATCH_TYPE_MARKET_PARTNER_OUTLET = 'MARKET_PARTNER_OUTLET';
	const DISPATCH_TYPE_SHOP_OUTLET = 'SHOP_OUTLET';

	const LIFT_NOT_NEEDED = 'NOT_NEEDED';
	const LIFT_MANUAL = 'MANUAL';
	const LIFT_ELEVATOR  = 'ELEVATOR';
	const LIFT_CARGO_ELEVATOR  = 'CARGO_ELEVATOR';
	const LIFT_FREE  = 'FREE';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function getDispatchTypes()
	{
		return [
			static::DISPATCH_TYPE_BUYER,
			static::DISPATCH_TYPE_MARKET_BRANDED_OUTLET,
			static::DISPATCH_TYPE_MARKET_PARTNER_OUTLET,
			static::DISPATCH_TYPE_SHOP_OUTLET,
		];
	}

	public function getDispatchTypeTitle($type)
	{
		return static::getLang('TRADING_SERVICE_MARKETPLACE_DELIVERY_DISPATCH_TYPE_' . Market\Data\TextString::toUpper($type), null, (string)$type);
	}

	public function needProcessBoxes($dispatchType)
	{
		return $this->isDispatchToMarketOutlet($dispatchType);
	}

	public function isDispatchToMarketOutlet($dispatchType)
	{
		return (
			$dispatchType === static::DISPATCH_TYPE_MARKET_BRANDED_OUTLET
			|| $dispatchType === static::DISPATCH_TYPE_MARKET_PARTNER_OUTLET
		);
	}

	public function getLiftTypes()
	{
		return [
			static::LIFT_NOT_NEEDED,
			static::LIFT_MANUAL,
			static::LIFT_ELEVATOR,
			static::LIFT_CARGO_ELEVATOR,
			static::LIFT_FREE,
		];
	}

	public function getLiftTitle($type, $version = '')
	{
		$suffix = ($version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '');

		return static::getLang('TRADING_SERVICE_MARKETPLACE_DELIVERY_LIFT_' . Market\Data\TextString::toUpper($type) . $suffix, null, (string)$type);
	}

	public function getLiftEnum()
	{
		$result = [];

		foreach ($this->getLiftTypes() as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => $this->getLiftTitle($type),
			];
		}

		return $result;
	}
}