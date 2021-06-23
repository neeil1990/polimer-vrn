<?php

namespace Yandex\Market\Trading\Setup;

class Facade
{
	public static function hasServiceSetup($serviceCode, $except = null)
	{
		$filter = [
			'=TRADING_SERVICE' => $serviceCode,
		];

		return static::hasSetupByFilter($filter, $except);
	}

	public static function hasServiceBehaviorSetup($serviceCode, $behaviorCode, $except = null)
	{
		$filter = [
			'=TRADING_SERVICE' => $serviceCode,
			'=TRADING_BEHAVIOR' => $behaviorCode,
		];

		return static::hasSetupByFilter($filter, $except);
	}

	public static function hasActiveSetup($siteId, $except = null)
	{
		$filter = [
			'=ACTIVE' => Table::BOOLEAN_Y,
			'=SITE_ID' => $siteId,
		];

		return static::hasSetupByFilter($filter, $except);
	}

	public static function hasActiveSetupUsingServiceCode($serviceCode, $except = null)
	{
		$filter = [
			'=ACTIVE' => Table::BOOLEAN_Y,
			'=TRADING_SERVICE' => $serviceCode,
		];

		return static::hasSetupByFilter($filter, $except);
	}

	public static function hasActiveSetupUsingExternalPlatform($externalId, $except = null)
	{
		$filter = [
			'=ACTIVE' => Table::BOOLEAN_Y,
			'=EXTERNAL_ID' => $externalId,
		];

		return static::hasSetupByFilter($filter, $except);
	}

	protected static function hasSetupByFilter($filter, $except = null)
	{
		if (!empty($except))
		{
			$filter['!=ID'] = $except;
		}

		$collection = Collection::loadByFilter([
			'filter' => $filter,
			'limit' => 1
		]);

		return (count($collection) > 0);
	}
}