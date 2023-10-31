<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;

class ParamUnit extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'unit',
		];
	}

	public function preselect(array $context)
	{
		return [
			'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_PROPERTY_FEATURE,
			'FIELD' => implode('.', [
				'iblock',
				'DETAIL_PAGE_SHOW',
				'UNIT',
			]),
		];
	}
}