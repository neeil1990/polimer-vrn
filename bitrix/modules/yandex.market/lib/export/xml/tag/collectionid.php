<?php
namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Export\Run\Steps\Offer\CollectionLink;

class CollectionId extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'collectionId',
			'multiple' => true,
		];
	}

	public function isDefined()
	{
		return true;
	}

	public function getDefinedSource(array $context = [])
	{
		return [
			'TYPE' => CollectionLink::SOURCE_TYPE,
			'FIELD' => 'COLLECTION_ID',
		];
	}
}