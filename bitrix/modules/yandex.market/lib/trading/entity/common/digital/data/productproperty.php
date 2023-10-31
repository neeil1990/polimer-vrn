<?php

namespace Yandex\Market\Trading\Entity\Common\Digital\Data;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market\Utils;
use Yandex\Market\Data\Number;
use Yandex\Market\Reference\Concerns;

class ProductProperty
{
	use Concerns\HasMessage;
	use Concerns\HasOnce;

	protected $settings;

	public function __construct(array $settings)
	{
		$this->settings = $settings;
	}

	public function getFields()
	{
		return [
			'SLIP_PROPERTY' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('SLIP_PROPERTY'),
				'HELP_MESSAGE' => self::getMessage('SLIP_PROPERTY_HELP'),
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'DETAIL_TEXT',
				],
			],
			'ACTIVATE_TILL_PROPERTY' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('ACTIVATE_TILL_PROPERTY'),
				'HELP_MESSAGE' => self::getMessage('ACTIVATE_TILL_PROPERTY_HELP', [
					'#DATE_UNTIL#' => ConvertTimeStamp(mktime(0, 0, 0, 1, 1, 2025)),
				]),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'ACTIVE_TO',
				],
			],
			'ACTIVATE_TILL_DEFAULT' => [
				'TYPE' => 'number',
				'NAME' => self::getMessage('ACTIVATE_TILL_DEFAULT'),
				'HELP_MESSAGE' => self::getMessage('ACTIVATE_TILL_DEFAULT_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 30,
				],
			],
		];
	}

	public function tryActivateTill(array $iblockElement)
	{
		try
		{
			$result = $this->activateTill($iblockElement);
		}
		catch (Main\ArgumentException $exception)
		{
			$result = null;
		}

		return $result;
	}

	public function activateTill(array $iblockElement)
	{
		try
		{
			return $this->activateTillValue($iblockElement);
		}
		catch (Main\ArgumentException $exception)
		{
			$default = Number::normalize($this->setting('ACTIVATE_TILL_DEFAULT'));

			if ($default > 0)
			{
				$result = new Main\Type\DateTime();
				$result->add(sprintf('P%sD', (int)$default));

				return $result;
			}

			throw $exception;
		}
	}

	protected function activateTillValue(array $iblockElement)
	{
		try
		{
			$result = $this->activateTillElementValue($iblockElement);
		}
		catch (Main\ArgumentException $exception)
		{
			$parentElement = $this->parentElement($iblockElement);

			if ($parentElement === null) { throw $exception; }

			$result = $this->activateTillElementValue($parentElement);
		}

		return $result;
	}

	protected function activateTillElementValue(array $iblockElement)
	{
		$property = $this->iblockElementValue($iblockElement, 'ACTIVATE_TILL');

		if (is_scalar($property['VALUE']) && is_numeric(trim($property['VALUE'])))
		{
			$days = (int)trim($property['VALUE']);
			$result = new Main\Type\DateTime();
			$result->add(sprintf('P%sD', $days));

			return $result;
		}

		if ($property['VALUE'] instanceof Main\Type\Date)
		{
			return $property['VALUE'];
		}

		$dateTime =
			Main\Type\DateTime::tryParse($property['VALUE'])
			?: Main\Type\DateTime::tryParse($property['VALUE'], 'Y-m-d H:i:s');

		if ($dateTime !== null)
		{
			return $dateTime;
		}

		list($from, $to) = Utils\Delivery\PeriodParser::parse($property['VALUE']);
		$period = $to ?: $from;

		if ($period !== null)
		{
			$result = new Main\Type\DateTime();
			$result->add($period);

			return $result;
		}

		throw new Main\ArgumentException(self::getMessage('ACTIVATE_TILL_PARSE_ERROR', [
			'#IBLOCK_ID#' => $iblockElement['IBLOCK_ID'],
			'#ID#' => $iblockElement['ID'],
			'#NAME#' => $iblockElement['NAME'],
			'#PROPERTY_NAME#' => isset($property['ID'])
				? sprintf('[%s] %s', $property['ID'], $property['NAME'])
				: $property['NAME'],
			'#VALUE#' => $property['VALUE'],
		]));
	}

	public function trySlip(array $iblockElement)
	{
		try
		{
			$result = $this->slip($iblockElement);
		}
		catch (Main\ArgumentException $exception)
		{
			$result = null;
		}

		return $result;
	}

	public function slip(array $iblockElement)
	{
		try
		{
			$property = $this->iblockElementValue($iblockElement, 'SLIP');
		}
		catch (Main\ArgumentException $exception)
		{
			$parentElement = $this->parentElement($iblockElement);

			if ($parentElement === null) { throw $exception; }

			$property = $this->iblockElementValue($parentElement, 'SLIP');
		}

		return $property['VALUE'];
	}

	protected function iblockElementValue(array $iblockElement, $code)
	{
		$propertyCode = trim($this->setting($code . '_PROPERTY'));

		if ($propertyCode === '')
		{
			throw new Main\ArgumentException(self::getMessage($code . '_UNDEFINED'));
		}

		$query = Iblock\PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $iblockElement['IBLOCK_ID'],
				'=ACTIVE' => true,
				'=CODE' => $propertyCode,
			],
			'select' => ['ID', 'NAME'],
			'limit' => 1,
		]);

		if ($property = $query->fetch())
		{
			$result = $this->iblockElementProperty($iblockElement, $code, $property);
		}
		else if (array_key_exists($propertyCode, $iblockElement))
		{
			$result = $this->iblockElementField($iblockElement, $code, $propertyCode);
		}
		else
		{
			throw new Main\ArgumentException(self::getMessage($code . '_NOT_EXISTS', [
				'#CODE#' => $propertyCode,
				'#IBLOCK_ID#' => $iblockElement['IBLOCK_ID'],
			]));
		}

		return $result;
	}

	protected function iblockElementProperty(array $iblockElement, $code, array $propertyRow)
	{
		$propertyValues = [
			$iblockElement['ID'] => [],
		];

		\CIBlockElement::GetPropertyValuesArray(
			$propertyValues,
			$iblockElement['IBLOCK_ID'],
			[ '=ID' => $iblockElement['ID'] ],
			[ 'ID' => $propertyRow['ID'] ],
			[ 'USE_PROPERTY_ID' => 'Y' ]
		);

		if (empty($propertyValues[$iblockElement['ID']]) || !is_array($propertyValues[$iblockElement['ID']]))
		{
			throw new Main\ArgumentException(self::getMessage($code . '_PROPERTY_EMPTY', [
				'#ID#' => $iblockElement['ID'],
				'#NAME#' => $iblockElement['NAME'],
				'#PROPERTY_NAME#' => $propertyRow['NAME'],
			]));
		}

		$property = reset($propertyValues[$iblockElement['ID']]);

		if (!empty($property['USER_TYPE']) && $property['USER_TYPE'] === 'HTML')
		{
			$property['VALUE'] = $property['~VALUE'];
		}

		if ($property['MULTIPLE'] === 'Y' && is_array($property['VALUE']))
		{
			$property['VALUE'] = reset($property['VALUE']);
		}

		if (!empty($property['USER_TYPE']))
		{
			$propertyUserType = \CIBlockProperty::getUserType($property['USER_TYPE']);

			if (isset($propertyUserType['GetPublicViewHTML']) && is_callable($propertyUserType['GetPublicViewHTML']))
			{
				$property['VALUE'] = call_user_func($propertyUserType['GetPublicViewHTML'], $property, [ 'VALUE' => $property['VALUE'] ], []);
			}
		}

		if (empty($property['VALUE']))
		{
			throw new Main\ArgumentException(self::getMessage($code . '_PROPERTY_EMPTY', [
				'#ID#' => $iblockElement['ID'],
				'#NAME#' => $iblockElement['NAME'],
				'#PROPERTY_NAME#' => $property['NAME'],
			]));
		}

		return $property;
	}

	protected function iblockElementField(array $iblockElement, $code, $field)
	{
		if (empty($iblockElement[$field]))
		{
			throw new Main\ArgumentException(self::getMessage($code . '_FIELD_EMPTY', [
				'#ID#' => $iblockElement['ID'],
				'#NAME#' => $iblockElement['NAME'],
				'#PROPERTY_NAME#' => $field,
			]));
		}

		$value = $iblockElement[$field];
		$column = Iblock\ElementTable::getEntity()->getField($field);

		if ($column instanceof Main\Entity\DatetimeField)
		{
			$timestamp = MakeTimeStamp($value, FORMAT_DATETIME);

			if ($timestamp === false)
			{
				throw new Main\ArgumentException(self::getMessage($code . '_FIELD_DATE_ERROR', [
					'#NAME#' => $field,
					'#VALUE#' => $value,
				]));
			}

			$value = Main\Type\DateTime::createFromTimestamp($timestamp);
		}
		else if ($field === 'DETAIL_TEXT' || $field === 'PREVIEW_TEXT')
		{
			$typeField = $field . '_TYPE';
			$type = isset($iblockElement[$typeField]) ? $iblockElement[$typeField] : null;

			$value = FormatText($value, $type);
		}

		return [
			'NAME' => $column !== null ? $column->getTitle() : $field,
			'VALUE' => $value,
		];
	}

	protected function setting($name, $default = null)
	{
		$value = isset($this->settings[$name]) ? $this->settings[$name] : null;

		if (Utils\Value::isEmpty($value))
		{
			$value = $default;
		}

		return $value;
	}

	protected function parentElement(array $iblockElement)
	{
		return $this->once('parentElement', [ $iblockElement['ID'], $iblockElement['IBLOCK_ID'] ], static function($elementId, $iblockId) {
			if (!Main\Loader::includeModule('catalog')) { return null; }

			$parent = \CCatalogSku::GetProductInfo($elementId, $iblockId);

			if (empty($parent['ID'])) { return null; }

			return \CIBlockElement::GetByID($parent['ID'])->Fetch() ?: null;
		});
	}
}