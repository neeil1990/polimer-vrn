<?php

namespace Yandex\Market\Reference\Storage\Field;

use Bitrix\Main;
use Yandex\Market;

class CanonicalDateTime extends Main\Entity\DatetimeField
{
	public function __construct($name, $parameters = [])
	{
		$parameters += [
			'fetch_data_modification' => [$this, 'getFetchModification'],
			'save_data_modification' => [$this, 'getSaveModification'],
		];

		parent::__construct($name, $parameters);

	}

	public function getDataType()
	{
		return 'datetime';
	}

	public function getFetchModification()
	{
		return [
			[$this, 'fetchToCanonical'],
		];
	}

	public function getSaveModification()
	{
		return [
			[$this, 'saveToCanonical'],
		];
	}

	public function fetchToCanonical($value)
	{
		if (!($value instanceof Main\Type\DateTime)) { return $value; }

		return Market\Data\DateTime::asCanonical($value);
	}

	public function saveToCanonical($value)
	{
		if (!($value instanceof Main\Type\DateTime)) { return $value; }

		return Market\Data\DateTime::toCanonical($value);
	}
}