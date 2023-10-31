<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Location extends Market\Trading\Entity\Reference\Location
{
	use Market\Reference\Concerns\HasMessage;

	protected $locationCache = [];
	protected $locationCacheKeys = [
		'LEFT_MARGIN' => true,
		'RIGHT_MARGIN' => true,
	];

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getLocation($serviceRegion)
	{
		$result = null;
		$regions = $this->linearizeRegion($serviceRegion);

		$searchMethods = [
			'ExternalIds' => [
				'field' => 'id',
			],
			'Name' => [
				'field' => [ 'name', 'type' ],
			],
			'City' => [
				'field' => 'name',
				'filter' => [
					'type' => 'CITY',
				],
			],
		];

		foreach ($searchMethods as $searchMethod => $searchArgument)
		{
			$method = 'searchLocationBy' . $searchMethod;
			$payload = $this->makeSearchLocationPayload($regions, $searchArgument);

			if (empty($payload)) { continue; }

			$map = $this->{$method}($payload, $result);

			foreach ($regions as $regionIndex => $region)
			{
				if (!isset($map[$region['id']])) { continue; }

				$result = $map[$region['id']];
				array_splice($regions, $regionIndex);
				break;
			}

			if (empty($regions)) { break; }
		}

		return $result;
	}

	protected function makeSearchLocationPayload($regions, $argument)
	{
		$field = $argument['field'];
		$filter = isset($argument['filter']) ? (array)$argument['filter'] : null;
		$result = [];

		foreach ($regions as $region)
		{
			if ($filter !== null && count(array_diff($filter, $region)) > 0)
			{
				continue;
			}

			$result[$region['id']] = is_array($field)
				? array_intersect_key($region, array_flip($field))
				: $region[$field];
		}

		return $result;
	}

	protected function linearizeRegion($serviceRegion)
	{
		$result = [];
		$level = $serviceRegion;

		do
		{
			$result[] = array_diff_key($level, [
				'parent' => true,
			]);

			$level = isset($level['parent']) ? $level['parent'] : null;
		}
		while ($level !== null);

		return $result;
	}

	protected function searchLocationByExternalIds($regionIds, $parentLocation = null)
	{
		if (empty($regionIds)) { return []; }

		$result = [];
		$parentFilter = $this->getParentLocationFilter($parentLocation, 'LOCATION');
		$commonFilter = [
			'=XML_ID' => array_values($regionIds),
			'=SERVICE.CODE' => 'YAMARKET',
		];

		$query = Sale\Location\ExternalTable::getList([
			'filter' => $commonFilter + $parentFilter,
			'select' => [ 'XML_ID', 'LOCATION_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['XML_ID']] = $row['LOCATION_ID'];
		}

		return $result;
	}

	protected function searchLocationByName($names, $parentLocation = null)
	{
		$result = [];
		$levelParents = [
			$parentLocation,
		];

		foreach (array_reverse($names, true) as $nameKey => $row)
		{
			$name = $row['name'];
			$type = $row['type'];
			$levelMatches = [];
			$parentFilter = $this->getFewParentsLocationFilter($levelParents);

			foreach ($this->splitMergedName($name) as $namePart)
			{
				$nameVariants = $this->synonymLocationName($namePart, $type);
				$locationId = $this->queryLocationByName($nameVariants, $parentFilter);

				if ($locationId === null && $type !== 'COUNTRY_DISTRICT' && $this->hasNameVariableSymbols($namePart))
				{
					$locationId = $this->queryLocationByName(
						$this->makeNameVariableLike($namePart),
						$parentFilter,
						''
					);
				}

				if ($locationId === null) { continue; }

				$levelMatches[] = $locationId;
			}

			if (empty($levelMatches)) { continue; }

			$levelParents = $levelMatches;
			$result[$nameKey] = end($levelMatches);
		}

		return $result;
	}

	protected function searchLocationByCity($names, $parentLocation = null)
	{
		$result = [];
		$loopParent = null; // ignore previous location
		$filter = [
			'=TYPE.CODE' => 'CITY',
		];

		foreach (array_reverse($names, true) as $nameKey => $name)
		{
			$loopFilter = $filter + $this->getParentLocationFilter($loopParent);
			$locationId = $this->queryLocationByName($name, $loopFilter);

			if ($locationId === null && $this->hasNameVariableSymbols($name))
			{
				$locationId = $this->queryLocationByName(
					$this->makeNameVariableLike($name),
					$loopFilter,
					''
				);
			}

			if ($locationId === null) { continue; }

			$result[$nameKey] = $locationId;
			$loopParent = $locationId;
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @param string $name
	 *
	 * @return string
	 */
	protected function splitLocationName($name)
	{
		$parts = $this->splitMergedName($name);

		return end($parts);
	}

	protected function splitMergedName($name)
	{
		$glue = (string)static::getMessage('MERGED_GLUE', null, '');

		if ($glue === '') { return [ $name ]; }

		$position = Market\Data\TextString::getPosition($name, $glue);

		if ($position === false) { return [ $name ]; }

		$glueLength = Market\Data\TextString::getLength($glue);
		$cityName =  Market\Data\TextString::getSubstring($name, 0, $position);
		$regionName = Market\Data\TextString::getSubstring($name, $position + $glueLength);

		if (!$this->isMergedNameRegionPart($regionName)) { return [ $name ]; }

		return [
			$cityName,
			$regionName
		];
	}

	protected function isMergedNameRegionPart($name)
	{
		$typeName = (string)self::getMessage('MERGED_REGION');

		if ($typeName === '') { return false; }

		return Market\Data\TextString::getPositionCaseInsensitive($name, $typeName) !== false;
	}

	protected function synonymLocationName($name, $type)
	{
		if ($type !== 'COUNTRY_DISTRICT') { return $name; }

		$mapEncoded = trim(self::getMessage('SYNONYM_' . $type));

		if ($mapEncoded === '') { return $name; }

		$mapParts = explode(PHP_EOL, $mapEncoded);
		$result = $name;

		foreach ($mapParts as $mapPart)
		{
			list($origin, $target) = explode(',', $mapPart);

			if ($origin === $name)
			{
				$result = [
					$name,
					$target,
				];
				break;
			}
		}

		return $result;
	}

	protected function queryLocationByName($names, array $filter = [], $compare = '=')
	{
		$result = null;

		$query = Sale\Location\LocationTable::getList(array(
			'filter' => $filter + [
				'=NAME.LANGUAGE_ID' => 'ru',
				$compare . 'NAME.NAME_UPPER' => is_array($names)
					? array_map(static function($name) { return Market\Data\TextString::toUpper($name); }, $names)
					: Market\Data\TextString::toUpper($names),
			],
			'select' => array_merge(
				[ 'ID', 'NAME' ],
				array_keys($this->locationCacheKeys)
			),
			'limit' => 1,
		));

		if ($row = $query->fetch())
		{
			$this->addLocationCache($row['ID'], $row);

			$result = $row['ID'];
		}

		return $result;
	}

	protected function hasNameVariableSymbols($name)
	{
		$result = false;

		foreach ($this->getVariableSymbols() as $symbol)
		{
			if (Market\Data\TextString::getPositionCaseInsensitive($name, $symbol) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function makeNameVariableLike($name)
	{
		foreach ($this->getVariableSymbols() as $symbol)
		{
			$name = str_replace($symbol, '_', $name);
		}

		return $name;
	}

	protected function getVariableSymbols()
	{
		$symbols = (string)static::getMessage('VARIABLE_SYMBOLS', null, '');

		return $symbols !== '' ? explode(',', $symbols) : [];
	}

	protected function getFewParentsLocationFilter($locationIds, $context = null)
	{
		$filters = [];
		$count = 0;

		foreach ($locationIds as $locationId)
		{
			$filter = $this->getParentLocationFilter($locationId, $context);

			if (!empty($filter))
			{
				$filters[] = $filter;
				++$count;
			}
		}

		if ($count > 1)
		{
			$result = [
				[ 'LOGIC' => 'OR' ] + $filters
			];
		}
		else if ($count === 1)
		{
			$result = reset($filters);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function getParentLocationFilter($locationId, $context = null)
	{
		if ($locationId === null) { return []; }

		$prefix = $context !== null ? $context . '.' : '';
		$row = $this->getLocationCache($locationId) ?: $this->fetchLocationCache($locationId);

		if ($row === null) { return []; }

		return [
			'>=' . $prefix . 'LEFT_MARGIN' => $row['LEFT_MARGIN'],
			'<=' . $prefix . 'RIGHT_MARGIN' => $row['RIGHT_MARGIN'],
		];
	}

	protected function addLocationCache($id, array $row)
	{
		if (isset($this->locationCache[$id])) { return; }

		$cacheValues = array_intersect_key($row, $this->locationCacheKeys);

		if (count($cacheValues) !== count($this->locationCacheKeys)) { return; }

		$this->locationCache[$id] = $cacheValues;
	}

	protected function getLocationCache($id)
	{
		return isset($this->locationCache[$id]) ? $this->locationCache[$id] : null;
	}

	protected function fetchLocationCache($id)
	{
		$result = null;

		$query = Sale\Location\LocationTable::getList([
			'filter' => [ '=ID' => $id ],
			'select' => array_keys($this->locationCacheKeys),
		]);

		if ($row = $query->fetch())
		{
			$this->addLocationCache($id, $row);

			$result = $row;
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
		$nextLocationId = (int)$locationId;
		$needServiceKeys = array_unique($serviceCodeMap);
		$usedLocationIds = [];

		do
		{
			// fetch services

			$queryServices = Sale\Location\ExternalTable::getList([
				'filter' => [
					'=LOCATION_ID' => $nextLocationId,
					'=SERVICE.CODE' => array_keys($serviceCodeMap),
				],
				'select' => [
					'XML_ID',
					'SERVICE_CODE' => 'SERVICE.CODE'
				],
			]);

			while ($service = $queryServices->fetch())
			{
				if (!isset($serviceCodeMap[$service['SERVICE_CODE']])) { continue; }

				$dataKey = $serviceCodeMap[$service['SERVICE_CODE']];
				$xmlId = (string)$service['XML_ID'];

				if ($xmlId !== '' && !isset($result[$dataKey]))
				{
					$result[$dataKey] = $xmlId;
				}
			}

			if (count($result) === count($needServiceKeys)) { break; }

			// set parent as next

			$queryLocation = Sale\Location\LocationTable::getList([
				'filter' => [ '=ID' => $nextLocationId ],
				'select' => [ 'PARENT_ID' ],
			]);
			$location = $queryLocation->fetch();

			if (!$location) { break; }

			$usedLocationIds[$nextLocationId] = true;
			$nextLocationId = (int)$location['PARENT_ID'];
		}
		while ($nextLocationId > 0 && !isset($usedLocationIds[$nextLocationId]));

		return $result;
	}
}