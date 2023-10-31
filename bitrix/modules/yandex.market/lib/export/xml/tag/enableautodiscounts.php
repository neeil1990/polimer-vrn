<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class EnableAutoDiscounts extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'enable_auto_discounts',
			'value_type' => Market\Type\Manager::TYPE_BOOLEAN,
		];
	}

	public function isDefined()
	{
		return $this->getParameter('global') ? true : false;
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		$result = null;

		if (!empty($context['ENABLE_AUTO_DISCOUNTS']) && $this->getParameter('global'))
		{
			$result = true;
		}

		return $result;
	}
}