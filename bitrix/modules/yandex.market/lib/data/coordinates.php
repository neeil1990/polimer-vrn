<?php

namespace Yandex\Market\Data;

class Coordinates
{
	const EARTH_RADIUS = 6371000;

	/** @noinspection PowerOperatorCanBeUsedInspection */
	public static function distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
	{
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);

		$latDelta = $latTo - $latFrom;
		$lonDelta = $lonTo - $lonFrom;

		$angle =
			2 * asin(sqrt(pow(sin($latDelta / 2), 2)
			+ cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

		return $angle * static::EARTH_RADIUS;
	}
}