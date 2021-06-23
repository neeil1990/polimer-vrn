<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order;

use Yandex\Market;
use Bitrix\Main;

class Buyer extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getMeaningfulFields()
	{
		return [
			'LAST_NAME',
			'FIRST_NAME',
			'MIDDLE_NAME',
			'PHONE',
		];
	}

	public static function getMeaningfulFieldTitle($fieldName)
	{
		return static::getLang('TRADING_ACTION_MODEL_BUYER_FIELD_' . $fieldName, null, $fieldName);
	}

	public function getId()
	{
		return (string)$this->getRequiredField('id');
	}

	public function getPhone()
	{
		return $this->getField('phone');
	}

	public function getLastName()
	{
		return $this->getField('lastName');
	}

	public function getFirstName()
	{
		return $this->getField('firstName');
	}

	public function getMiddleName()
	{
		return $this->getField('middleName');
	}

	public function getMeaningfulValues()
	{
		return array_filter([
			'ID' => $this->getId(),
			'LAST_NAME' => $this->getLastName(),
			'FIRST_NAME' => $this->getFirstName(),
			'MIDDLE_NAME' => $this->getMiddleName(),
			'PHONE' => $this->getPhone(),
		]);
	}
}