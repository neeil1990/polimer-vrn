<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Export\Entity;

class SetIds extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'set-ids',
			'union' => true,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		return [
			[
				'TYPE' => Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'TAGS',
			],
		];
	}
}