<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Profile extends Market\Trading\Entity\Reference\Profile
{
	use Market\Reference\Concerns\HasLang;

	/** @var Environment*/
	protected $environment;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getEnum($userId, $personTypeId)
	{
		$result = [];

		$query = Sale\Internals\UserPropsTable::getList([
			'filter' => [
				'=USER_ID' => $userId,
				'=PERSON_TYPE_ID' => (int)$personTypeId,
			],
			'select' => [ 'ID', 'NAME' ],
			'order' => [ 'DATE_UPDATE' => 'DESC', 'ID' => 'DESC' ]
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => $row['NAME']
			];
		}

		return $result;
	}

	public function getValues($profileId)
	{
		if (method_exists(Sale\OrderUserProperties::class, 'getProfileValues'))
		{
			$result = Sale\OrderUserProperties::getProfileValues($profileId);
		}
		else
		{
			$result = $this->loadProfileValues($profileId);
		}

		return $result;
	}

	protected function getUserValues($userId, $personTypeId)
	{
		if (method_exists(Sale\OrderUserProperties::class, 'loadProfiles'))
		{
			$loadResult = Sale\OrderUserProperties::loadProfiles($userId, $personTypeId);
			$loadData = $loadResult->getData();
			$result = [];

			if (isset($loadData[$personTypeId]))
			{
				foreach ($loadData[$personTypeId] as $profileId => $profileData)
				{
					$result[$profileId] = $profileData['VALUES'];
				}
			}
		}
		else
		{
			$enum = $this->getEnum($userId, $personTypeId);
			$profileIds = array_column($enum, 'ID');

			if (!empty($profileIds))
			{
				$sortMap = array_flip($profileIds);
				$result = $this->loadFewProfileValues([
					'filter' => [ '=USER_PROPS_ID' => $profileIds ],
				]);

				uksort($result, static function($a, $b) use ($sortMap) {
					$aSort = isset($sortMap[$a]) ? $sortMap[$a] : 500;
					$bSort = isset($sortMap[$b]) ? $sortMap[$b] : 500;

					if ($aSort === $bSort) { return 0; }

					return $aSort < $bSort ? -1 : 1;
				});
			}
			else
			{
				$result = [];
			}
		}

		return $result;
	}

	public function searchRaw($userId, $personTypeId, array $rawValues)
	{
		$result = null;

		foreach ($this->getUserValues($userId, $personTypeId) as $profileId => $storedValues)
		{
			if ($this->isMatchValues($storedValues, $rawValues))
			{
				$result = $profileId;
				break;
			}
		}

		return $result;
	}

	protected function isMatchValues($stored, $new)
	{
		$result = true;

		foreach ($new as $key => $value)
		{
			if (!isset($stored[$key])) { continue; }

			/** @noinspection TypeUnsafeComparisonInspection */
			if ($value != $stored[$key])
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	public function add($userId, $personTypeId, array $values = [])
	{
		$values += $this->getDefaultValues();
		$profileName = $this->makeProfileName($values, true);
		$profileValues = $this->convertPropertyValues($personTypeId, $values);

		return $this->addRaw($userId, $personTypeId, $profileName, $profileValues);
	}

	public function addRaw($userId, $personTypeId, $profileName, array $rawValues = [])
	{
		$addResult = $this->addProfile($userId, $personTypeId, $profileName);

		if (!empty($rawValues) && $addResult->isSuccess())
		{
			$profileId = $addResult->getId();
			$valuesResult = $this->saveProfileValues($userId, $personTypeId, $profileId, $rawValues);

			Market\Result\Facade::merge([$addResult, $valuesResult]);
		}

		return $addResult;
	}

	public function update($profileId, array $values)
	{
		$profile = $this->fetchProfile($profileId);
		$profileName = $this->makeProfileName($values);
		$profileValues = $this->convertPropertyValues($profile['PERSON_TYPE_ID'], $values);

		return $this->doUpdateProfile($profile, $profileName, $profileValues);
	}

	public function updateRaw($profileId, $profileName, array $rawValues = [])
	{
		$profile = $this->fetchProfile($profileId);

		return $this->doUpdateProfile($profile, $profileName, $rawValues);
	}

	protected function doUpdateProfile($profile, $profileName, array $propertyValues)
	{
		$updateResults = [];

		if ($profileName !== null && $profile['NAME'] !== $profileName)
		{
			$updateResults[] = $this->updateProfile($profile['ID'], $profileName);
		}

		if (!empty($propertyValues))
		{
			$updateResults[] = $this->saveProfileValues($profile['USER_ID'], $profile['PERSON_TYPE_ID'], $profile['ID'], $propertyValues);
		}

		return !empty($updateResults)
			? Market\Result\Facade::merge($updateResults)
			: new Main\Entity\UpdateResult();
	}

	public function getEditUrl($profileId)
	{
		return Market\Ui\Admin\Path::getPageUrl('sale_buyers_profile_edit', [
			'id' => (int)$profileId,
			'lang' => LANGUAGE_ID,
		]);
	}

	protected function fetchProfile($profileId)
	{
		$query = Sale\Internals\UserPropsTable::getById($profileId);
		$result = $query->fetch();

		if ($result === false)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_PROFILE_NOT_FOUND', [
				'#ID#' => $profileId,
			]);

			throw new Main\ObjectNotFoundException($errorMessage);
		}

		return $result;
	}

	protected function addProfile($userId, $personTypeId, $name)
	{
		$result = new Main\Entity\AddResult();

		$addResult = Sale\Internals\UserPropsTable::add([
			'NAME' => $name,
			'USER_ID' => $userId,
			'PERSON_TYPE_ID' => $personTypeId,
			'DATE_UPDATE' => new Main\Type\DateTime(),
		]);

		if ($addResult->isSuccess())
		{
			$result->setId($addResult->getId());
		}
		else
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_PROFILE_CANT_ADD_PROFILE', [
				'#MESSAGE#' => implode(PHP_EOL, $addResult->getErrorMessages()),
			]);
			$error = new Main\Entity\EntityError($errorMessage);

			$result->addError($error);
		}

		return $result;
	}

	protected function updateProfile($profileId, $name)
	{
		return Sale\Internals\UserPropsTable::update($profileId, [
			'NAME' => $name,
		]);
	}

	protected function saveProfileValues($userId, $personTypeId, $profileId, $values)
	{
		$result = new Main\Entity\UpdateResult();
		$errors = [];

		\CSaleOrderUserProps::DoSaveUserProfile($userId, $profileId, '', $personTypeId, $values, $errors);

		foreach ($errors as $error)
		{
			$result->addError(new Main\Error($error['TEXT'], $error['CODE']));
		}

		return $result;
	}

	protected function loadProfileValues($profileId)
	{
		$fewProfiles = $this->loadFewProfileValues([
			'filter' => [ '=USER_PROPS_ID' => (int)$profileId ],
		]);

		return isset($fewProfiles[$profileId]) ? $fewProfiles[$profileId] : [];
	}

	protected function loadFewProfileValues(array $parameters)
	{
		$result = [];
		$requiredSelect = [
			'VALUE',
			'ORDER_PROPS_ID',
			'USER_PROPS_ID',
			'PROPERTY_TYPE' => 'PROPERTY.TYPE',
		];

		if (!isset($parameters['select']))
		{
			$parameters['select'] = $requiredSelect;
		}
		else
		{
			$parameters['select'] = array_unique(array_merge(
				$parameters['select'],
				$requiredSelect
			));
		}

		$query = Sale\Internals\UserPropsValueTable::getList($parameters);

		while ($row = $query->fetch())
		{
			$profileId = (int)$row['USER_PROPS_ID'];
			$propertyId = (int)$row['ORDER_PROPS_ID'];
			$value = $row['VALUE'];

			if ($row['PROPERTY_TYPE'] === 'ENUM')
			{
				$value = explode(',', $value);
			}

			if ($row['PROPERTY_TYPE'] === 'LOCATION' && !empty($value))
			{
				$value = \CSaleLocation::getLocationCODEbyID($value);
			}

			if (!isset($result[$profileId]))
			{
				$result[$profileId] = [];
			}

			$result[$profileId][$propertyId] = $value;
		}

		return $result;
	}

	protected function getDefaultValues()
	{
		$result = [];
		$fields = [
			'NAME',
			'COMPANY',
			'LOCATION',
			'ADDRESS',
			'ZIP',
			'PHONE',
			'EMAIL',
		];

		foreach ($fields as $fieldName)
		{
			if ($fieldName === 'LOCATION')
			{
				$geoId = $this->getPropertyDefaultValue('GEO_ID');
				$cityName = $this->getPropertyDefaultValue('CITY');

				$value = $this->getLocationValueByName($cityName, $geoId);
			}
			else
			{
				$value = $this->getPropertyDefaultValue($fieldName);
			}

			if (!Market\Utils\Value::isEmpty($value))
			{
				$result[$fieldName] = $value;
			}
		}

		return $result;
	}

	protected function convertPropertyValues($personTypeId, array $values)
	{
		$propertyType = $this->environment->getProperty();

		return $propertyType->convertMeaningfulValues($personTypeId, $values);
	}

	protected function makeProfileName(array $values, $useDefault = false)
	{
		if (isset($values['NAME']))
		{
			$result = $values['NAME'];
		}
		else if ($useDefault)
		{
			$result = static::getLang('TRADING_ENTITY_SALE_PROFILE_VALUE_NAME');
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function getLocationValueByName($name, $geoId)
	{
		$result = null;
		$locationType = $this->environment->getLocation();
		$locationId = $locationType->getLocation([
			'id' => $geoId,
			'name' => $name,
		]);

		if ($locationId !== null)
		{
			$result = \CSaleLocation::getLocationCODEbyID($locationId);
		}

		return $result;
	}

	protected function getPropertyDefaultValue($propertyType, $defaultValue = '')
	{
		return (string)static::getLang(
			'TRADING_ENTITY_SALE_PROFILE_VALUE_' . $propertyType,
			null,
			(string)$defaultValue
		);
	}
}