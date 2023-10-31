<?php
namespace Yandex\Market\Ui\Export\Promo;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui;
use Yandex\Market\Export;
use Yandex\Market\Utils;
use Yandex\Market\Logger;

class RunForm extends Ui\Export\Reference\EntityRunForm
{
	use Concerns\HasMessage;

	protected function finishResultUrl(array $query)
	{
		return Ui\Admin\Path::getModuleUrl('promo_result', $query);
	}

	protected function entityType()
	{
		return Export\Run\Manager::ENTITY_TYPE_PROMO;
	}

	protected function logEntityTypes()
	{
		return [
			Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO,
			Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT,
			Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT,
			Logger\Table::ENTITY_TYPE_EXPORT_RUN_GIFT,
		];
	}

	protected function getTabControlId()
	{
		return 'YANDEX_MARKET_ADMIN_PROMO_RUN';
	}

	protected function models(array $ids = null)
	{
		$filter = $this->idsFilter($ids);

		if ($filter === null) { return []; }

		$models = Export\Promo\Model::loadList([ 'filter' => $filter ]);
		$modelsMapped = [];

		foreach ($models as $model)
		{
			$modelsMapped[$model->getId()] = $model;
		}

		return $modelsMapped;
	}

	protected function linked($entityId)
	{
		$query = Export\Promo\Internals\SetupLinkTable::getList([
			'filter' => [ '=PROMO_ID' => $entityId ],
			'select' => [ 'SETUP_ID' ],
		]);

		return array_column($query->fetchAll(), 'SETUP_ID', 'SETUP_ID');
	}

	protected function exported(array $ids = null)
	{
		$filter = $this->idsFilter($ids, 'ELEMENT_ID');

		if ($filter === null) { return []; }

		$query = Export\Run\Storage\PromoTable::getList([
			'filter' =>
				[ '=STATUS' => Export\Run\Steps\Promo::STORAGE_STATUS_SUCCESS ]
				+ $filter,
			'select' => [ 'SETUP_ID', 'ELEMENT_ID' ],
			'group' => [ 'SETUP_ID', 'ELEMENT_ID' ],
		]);

		return Utils\ArrayHelper::groupBy($query->fetchAll(), 'ELEMENT_ID');
	}
}