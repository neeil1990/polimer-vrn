<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class PersonType extends Market\Trading\Entity\Reference\PersonType
{
	/** @var Environment */
	protected $environment;

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getEnum($siteId = null)
	{
		$result = [];
		$filter = [
			'=ACTIVE' => 'Y',
		];

		if ($this->hasRegistryType())
		{
			$filter['=ENTITY_REGISTRY_TYPE'] = Sale\Registry::REGISTRY_TYPE_ORDER;
		}

		if ($siteId !== null)
		{
			$filter['=PERSON_TYPE_SITE.SITE_ID'] = $siteId;
		}

		$query = Sale\Internals\PersonTypeTable::getList([
			'filter' => $filter,
			'select' => [ 'ID', 'NAME' ],
			'order' => [ 'SORT' => 'ASC', 'ID' => 'ASC' ]
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => $row['NAME'],
			];
		}

		return $result;
	}

	public function getIndividualId($siteId = null)
	{
		return $this->getIdByDomain(Sale\BusinessValue::INDIVIDUAL_DOMAIN, $siteId);
	}

	public function getLegalId($siteId = null)
	{
		return $this->getIdByDomain(Sale\BusinessValue::ENTITY_DOMAIN, $siteId);
	}

	protected function getIdByDomain($type, $siteId = null)
	{
		$result = null;
		$filter = [
			'=DOMAIN' => $type,
			'=PERSON_TYPE_REFERENCE.ACTIVE' => 'Y'
		];

		if ($this->hasRegistryType())
		{
			$filter['=PERSON_TYPE_REFERENCE.ENTITY_REGISTRY_TYPE'] = Sale\Registry::REGISTRY_TYPE_ORDER;
		}

		if ($siteId !== null)
		{
			$filter['=PERSON_TYPE_REFERENCE.PERSON_TYPE_SITE.SITE_ID'] = $siteId;
		}

		$query = Sale\Internals\BusinessValuePersonDomainTable::getList([
			'filter' => $filter,
			'limit' => 1,
			'select' => [ 'PERSON_TYPE_ID' ]
		]);

		if ($row = $query->fetch())
		{
			$result = $row['PERSON_TYPE_ID'];
		}

		return $result;
	}

	protected function hasRegistryType()
	{
		$result = false;

		if (class_exists(Sale\Registry::class))
		{
			$personTypeEntity = Sale\Internals\PersonTypeTable::getEntity();
			$result = $personTypeEntity->hasField('ENTITY_REGISTRY_TYPE');
		}

		return $result;
	}
}