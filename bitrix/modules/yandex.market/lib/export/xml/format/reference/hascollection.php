<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

trait HasCollection
{
	public function getCollectionParentName()
	{
		return 'collections';
	}

	public function getCollection()
	{
		return new Xml\Tag\Base([
			'name' => 'collection',
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'required' => true, 'primary' => true]),
			],
			'children' => [
				new Xml\Tag\Base([ 'name' => 'url', 'required' => true, 'value_type' => Type\Manager::TYPE_URL ]),
				new Xml\Tag\Base([ 'name' => 'picture', 'multiple' => true, 'max_count' => 5, 'value_type' => Type\Manager::TYPE_FILE ]),
				new Xml\Tag\Base([ 'name' => 'name', 'required' => true, 'multiple' => true ]),
				new Xml\Tag\Base([ 'name' => 'description', 'value_type' => Type\Manager::TYPE_HTML ]),
			],
		]);
	}

	public function getCollectionId()
	{
		return new Xml\Tag\CollectionId(['multiple' => true]);
	}
}