<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Property extends Market\Trading\Entity\Reference\Property
{
	/** @var Environment */
	protected $environment;

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getEnum($personTypeId)
	{
		$result = [];
		$personTypeId = (int)$personTypeId;

		if ($personTypeId > 0)
		{
			$query = Sale\Internals\OrderPropsTable::getList([
				'filter' => [
					'=PERSON_TYPE_ID' => $personTypeId,
					'=ACTIVE' => 'Y',
				],
				'order' => [
					'SORT' => 'asc',
					'ID' => 'asc',
				],
			]);

			while ($propertyRow = $query->fetch())
			{
				$result[] = [
					'ID' => $propertyRow['ID'],
					'VALUE' => $propertyRow['NAME'],
					'TYPE' => $this->getPropertyType($propertyRow),
					'CODE' => $propertyRow['CODE'],
				];
			}
		}

		return $result;
	}

	public function getEditUrl($propertyId)
	{
		return Market\Ui\Admin\Path::getPageUrl('sale_order_props_edit', [
			'ID' => (int)$propertyId,
			'lang' => LANGUAGE_ID,
		]);
	}

	public function add($personTypeId, $fields)
	{
		$propertyResult = $this->addProperty($personTypeId, $fields);

		if (
			isset($fields['TYPE'], $fields['VARIANTS'])
			&& $fields['TYPE'] === 'ENUM'
			&& $propertyResult->isSuccess()
		)
		{
			$this->addPropertyEnum($propertyResult->getId(), $fields['VARIANTS']);
		}

		return $propertyResult;
	}

	protected function addProperty($personTypeId, $fields)
	{
		$tableFields = Sale\Internals\OrderPropsTable::getEntity()->getScalarFields();
		$propertyFields = $fields + [
			'TYPE' => 'STRING',
			'PERSON_TYPE_ID' => $personTypeId,
			'PROPS_GROUP_ID' => $this->getPropertyDefaultGroup($personTypeId),
			'ACTIVE' => 'Y',
			'UTIL' => 'Y',
		];
		$propertyFields = array_intersect_key($propertyFields, $tableFields);

		if (isset($tableFields['ENTITY_REGISTRY_TYPE']))
		{
			$propertyFields['ENTITY_REGISTRY_TYPE'] = Sale\Registry::REGISTRY_TYPE_ORDER;
		}

		return Sale\Internals\OrderPropsTable::add($propertyFields);
	}

	protected function getPropertyDefaultGroup($personTypeId)
	{
		$result = null;

		$query = Sale\Internals\OrderPropsGroupTable::getList([
			'select' => [ 'ID' ],
			'filter' => [ '=PERSON_TYPE_ID' => $personTypeId ],
			'order' => [ 'SORT' => 'ASC', 'ID' => 'ASC' ],
		]);

		if ($row = $query->fetch())
		{
			$result = $row['ID'];
		}

		return $result;
	}

	protected function addPropertyEnum($propertyId, $variants)
	{
		$result = new Main\Result();

		foreach ($variants as $variant)
		{
			$addResult = Sale\Internals\OrderPropsVariantTable::add([
				'ORDER_PROPS_ID' => $propertyId,
				'VALUE' => $variant['ID'],
				'NAME' => $variant['VALUE'],
			]);

			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
		}

		return $result;
	}

	public function convertMeaningfulValues($personTypeId, array $values)
	{
		$enum = $this->getEnum($personTypeId);
		$result = [];

		foreach ($enum as $option)
		{
			if (isset($option['TYPE'], $values[$option['TYPE']]))
			{
				$result[$option['ID']] = $values[$option['TYPE']];
			}
		}

		return $result;
	}

	public function formatMeaningfulValues($personTypeId, array $values)
	{
		if (isset($values['PHONE']))
		{
			$values['PHONE'] = Market\Data\Phone::format($values['PHONE']);
		}

		return $values;
	}

	public function joinPropertyMultipleValue(Sale\PropertyValue $property, $value)
	{
		$propertyRow = $property->getProperty();
		$propertyType = isset($propertyRow['TYPE']) ? $propertyRow['TYPE'] : 'STRING';
		$supportsGlue = [
			'STRING' => true,
			'ADDRESS' => ', ',
		];

		if (!isset($supportsGlue[$propertyType]))
		{
			$result = reset($value);
		}
		else
		{
			if (is_string($supportsGlue[$propertyType]))
			{
				$glue = $supportsGlue[$propertyType];
			}
			else
			{
				$propertyType = $this->getPropertyType($propertyRow);
				$propertyCode = isset($propertyRow['CODE']) ? (string)$propertyRow['CODE'] : '';

				$glue =
					$this->resolvePropertyTypeValueGlue($propertyType)
						?: $this->resolvePropertyCodeValueGlue($propertyCode)
						?: ', ';
			}

			$result = implode($glue, $value);
		}

		return $result;
	}

	protected function resolvePropertyTypeValueGlue($propertyType)
	{
		switch ($propertyType)
		{
			case 'NAME':
				$result = ' ';
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	protected function resolvePropertyCodeValueGlue($propertyCode)
	{
		$result = null;
		$types = [
			'INTERVAL' => '-',
			'PERIOD' => '-',
			'TIME' => '-',
		];

		foreach ($types as $type => $glue)
		{
			if (Market\Data\TextString::getPositionCaseInsensitive($propertyCode, $type) !== false)
			{
				$result = $glue;
				break;
			}
		}

		return $result;
	}

	protected function getPropertyType($propertyRow)
	{
		$propertyCode = Market\Data\TextString::toUpper($propertyRow['CODE']);
		$propertyType = null;

		if ($propertyRow['IS_EMAIL'] === 'Y' || $this->isMatchPropertyCode($propertyCode, ['EMAIL']))
		{
			$propertyType = 'EMAIL';
		}
		else if ($propertyRow['IS_PHONE'] === 'Y' || $this->isMatchPropertyCode($propertyCode, ['PHONE', 'TEL']))
		{
			$propertyType = 'PHONE';
		}
		else if ($propertyRow['IS_LOCATION'] === 'Y')
		{
			$propertyType = 'LOCATION';
		}
		else if ($propertyRow['IS_ADDRESS'] === 'Y' || $this->isMatchPropertyCode($propertyCode, ['ADDRESS', 'COMPANY_ADR', 'COMPANY_ADDRESS']))
		{
			$propertyType = 'ADDRESS';
		}
		else if ($propertyRow['IS_ZIP'] === 'Y' || $propertyCode === 'ZIP' || $propertyCode === 'INDEX')
		{
			$propertyType = 'ZIP';
		}
		else if ($this->isMatchPropertyCode($propertyCode, ['CITY']))
		{
			$propertyType = 'CITY';
		}
		else if ($propertyCode === 'COMPANY')
		{
			$propertyType = 'COMPANY';
		}
		else if ($propertyRow['IS_PROFILE_NAME'] === 'Y' || $propertyRow['IS_PAYER'] === 'Y')
		{
			$propertyType = 'NAME';
		}

		return $propertyType;
	}

	protected function isMatchPropertyCode($haystack, $needles)
	{
		$haystack = Market\Data\TextString::toUpper($haystack);
		$result = false;

		foreach ($needles as $needle)
		{
			if (Market\Data\TextString::getPosition($haystack, $needle) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}