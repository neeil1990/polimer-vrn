<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Cpa extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'cpa',
			'value_type' => Market\Type\Manager::TYPE_BOOLEAN,
			'overrides' => [
				'true' => '1',
				'false' => '0',
			],
		];
	}

	public function isDefined()
	{
		return (bool)$this->getParameter('global');
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		$result = null;

		if ($this->getParameter('global'))
		{
			$result = !empty($context['ENABLE_CPA']) ? true : null;
		}

		return $result;
	}
}