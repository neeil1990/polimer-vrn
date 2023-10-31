<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

use Yandex\Market\Export\Xml;

trait HasCurrency
{
	public function getCurrencyParentName()
	{
		return 'currencies';
	}

	public function getCurrency()
	{
		return new Xml\Tag\Base([
			'name' => 'currency',
			'empty_value' => true,
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'value_type' => 'currency', 'required' => true, 'primary' => true]),
				new Xml\Attribute\Base(['name' => 'rate', 'required' => true]),
			],
		]);
	}
}