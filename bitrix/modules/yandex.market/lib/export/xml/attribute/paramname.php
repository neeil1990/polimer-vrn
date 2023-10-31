<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;

class ParamName extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'name',
		];
	}

	public function preselect(array $context)
	{
		return [
			'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_PROPERTY_FEATURE,
			'FIELD' => implode('.', [
				'iblock',
				'DETAIL_PAGE_SHOW',
				'NAME',
			]),
		];
	}
}