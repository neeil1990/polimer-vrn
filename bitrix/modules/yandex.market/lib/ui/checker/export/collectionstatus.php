<?php
namespace Yandex\Market\Ui\Checker\Export;

use Yandex\Market\Export;

class CollectionStatus extends EntityStatus
{
	public function getTitle()
	{
		return $this->getMessage('TITLE_COLLECTION');
	}

	protected function entityType()
	{
		return Export\Run\Manager::ENTITY_TYPE_COLLECTION;
	}

	protected function models()
	{
		return Export\Collection\Model::loadList();
	}
}