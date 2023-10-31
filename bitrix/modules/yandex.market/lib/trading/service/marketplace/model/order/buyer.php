<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order;

use Yandex\Market;

class Buyer extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasMessage;

	const TYPE_PERSON = 'PERSON';
	const TYPE_BUSINESS = 'BUSINESS';

	public static function getMeaningfulFields()
	{
		return [
			'BUYER_TYPE',
		];
	}

	public static function getMeaningfulFieldTitle($fieldName)
	{
		return self::getMessage('FIELD_' . $fieldName, null, $fieldName);
	}

	public static function getTypeTitle($type)
	{
		return self::getMessage('TYPE_' . $type, null, $type);
	}

	public function getType()
	{
		return $this->getField('type');
	}

	public function getMeaningfulValues()
	{
		return array_filter([
			'BUYER_TYPE' => new Market\Data\Type\EnumValue(
				$this->getType(),
				static::getTypeTitle($this->getType())
			),
		]);
	}
}