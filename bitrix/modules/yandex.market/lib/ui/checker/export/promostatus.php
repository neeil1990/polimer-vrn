<?php
namespace Yandex\Market\Ui\Checker\Export;

use Yandex\Market\Export;

class PromoStatus extends EntityStatus
{
	public function getTitle()
	{
		return $this->getMessage('TITLE_PROMO');
	}

	protected function entityType()
	{
		return Export\Run\Manager::ENTITY_TYPE_PROMO;
	}

	protected function models()
	{
		return Export\Promo\Model::loadList();
	}
}