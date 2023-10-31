<?php

namespace Yandex\Market\Trading\Service\Turbo\Model\Order;

use Yandex\Market;
use Bitrix\Main;

class User extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getMeaningfulFields()
	{
		return [
			'NAME',
			'EMAIL',
			'PHONE',
		];
	}

	public static function getMeaningfulFieldTitle($fieldName)
	{
		return static::getLang('TRADING_ACTION_MODEL_USER_FIELD_' . $fieldName, null, $fieldName);
	}

	public function getId()
	{
		return (string)$this->getRequiredField('id');
	}

	public function getEmail()
	{
		return $this->getField('email');
	}

	public function getPhone()
	{
		return $this->getField('phone');
	}

	public function getName()
	{
		return $this->getField('name');
	}

	public function getMeaningfulValues()
	{
		return array_filter([
			'ID' => $this->getId(),
			'NAME' => $this->getName(),
			'EMAIL' => $this->getEmail(),
			'PHONE' => $this->getPhone(),
		]);
	}
}