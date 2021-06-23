<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Location extends Market\Trading\Entity\Reference\Location
{
	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getLocation($serviceRegion)
	{
		$result = null;
		$mapper = new \CSaleYMLocation;

		if (method_exists($mapper, 'getLocationId'))
		{
			$locationId = $mapper->getLocationId($serviceRegion);
		}
		else
		{
			$locationId = $mapper->getLocationByCityName($serviceRegion['name']);
		}

		if ($locationId !== false)
		{
			$result = $locationId;
		}

		return $result;
	}

	public function getMeaningfulValues($locationId)
	{
		$externalData = $this->fetchLocationExternalData($locationId, [
			'ZIP' => 'ZIP',
			'ZIP_LOWER' => 'ZIP',
			'LAT' => 'LAT',
			'LATITUDE' => 'LAT',
			'LON' => 'LON',
			'LONGITUDE' => 'LON',
		]);

		return array_filter($externalData);
	}

	protected function fetchLocationExternalData($locationId, $serviceCodeMap)
	{
		$result = [];

		$query = Sale\Location\ExternalTable::getList([
			'filter' => [
				'=LOCATION.ID' => $locationId,
				'=SERVICE.CODE' => array_keys($serviceCodeMap),
			],
			'select' => [
				'XML_ID',
				'SERVICE_CODE' => 'SERVICE.CODE'
			],
		]);

		while ($row = $query->fetch())
		{
			if (!isset($serviceCodeMap[$row['SERVICE_CODE']])) { continue; }

			$dataKey = $serviceCodeMap[$row['SERVICE_CODE']];
			$xmlId = (string)$row['XML_ID'];

			if ($xmlId !== '' && !isset($result[$dataKey]))
			{
				$result[$dataKey] = $xmlId;
			}
		}

		return $result;
	}
}