<?php
namespace Yandex\Market\Export\Filter;

use Yandex\Market\Export\Entity;

class Facade
{
	public static function compile(array $elementFilter, array $offerFilter = [])
	{
		return
			static::parse($elementFilter, Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD, Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY)
			+ static::parse($offerFilter, Entity\Manager::TYPE_IBLOCK_OFFER_FIELD, Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY);
	}

	protected static function parse(array $filter, $fieldSource, $propertySource)
	{
		$result = [];

		foreach ($filter as $name => $value)
		{
			if ($name === 'LOGIC')
			{
				$result[$name] = $value;
				continue;
			}

			if (is_numeric($name) && is_array($value))
			{
				$result[] = static::parse($value, $fieldSource, $propertySource);
				continue;
			}

			if ($name === 'INCLUDE_SUBSECTIONS') { continue; }

			$operation = \CIBlock::MkOperationFilter($name);
			$type = $fieldSource;
			$field = $operation['FIELD'];
			$compare = $operation['PREFIX'];

			if (mb_strpos($field, 'PROPERTY_'))
			{
				$type = $propertySource;
				$field = mb_substr($field, mb_strlen('PROPERTY_'));
			}
			else if (
				($field === 'SECTION_ID' || $field === 'IBLOCK_SECTION_ID')
				&& (!isset($filter['INCLUDE_SUBSECTIONS']) || $filter['INCLUDE_SUBSECTIONS'] !== 'Y')
			)
			{
				$field = 'STRICT_SECTION_ID';
			}

			if (!isset($result[$type]))
			{
				$result[$type] = [];
			}

			$result[$type][] = [
				'FIELD' => $field,
				'COMPARE' => $compare,
				'VALUE' => $value,
			];
		}

		return $result;
	}
}