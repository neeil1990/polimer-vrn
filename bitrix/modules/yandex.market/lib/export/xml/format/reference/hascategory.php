<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

use Yandex\Market\Export\Xml;

trait HasCategory
{
	public function getCategoryParentName()
	{
		return 'categories';
	}

	public function getCategory()
	{
		return new Xml\Tag\Base([
			'name' => 'category',
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'required' => true, 'primary' => true]),
				new Xml\Attribute\Base(['name' => 'parentId']),
			],
		]);
	}
}