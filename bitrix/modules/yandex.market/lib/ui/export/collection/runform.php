<?php
namespace Yandex\Market\Ui\Export\Collection;

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
		return Ui\Admin\Path::getModuleUrl('collection_result', $query);
	}

	protected function entityType()
	{
		return Export\Run\Manager::ENTITY_TYPE_COLLECTION;
	}

	protected function logEntityTypes()
	{
		return [
			Logger\Table::ENTITY_TYPE_EXPORT_RUN_COLLECTION,
		];
	}

	protected function getTabControlId()
	{
		return 'YANDEX_MARKET_ADMIN_COLLECTION_RUN';
	}

	protected function models(array $ids = null)
	{
		$filter = $this->idsFilter($ids);

		if ($filter === null) { return []; }

		$models = Export\Collection\Model::loadList([ 'filter' => $filter ]);
		$modelsMapped = [];

		foreach ($models as $model)
		{
			$modelsMapped[$model->getId()] = $model;
		}

		return $modelsMapped;
	}

	protected function exported(array $ids = null)
	{
		$filter = $this->idsFilter($ids, 'COLLECTION_ID');

		if ($filter === null) { return []; }

		$query = Export\Run\Storage\CollectionTable::getList([
			'filter' =>
				$filter
				+ [ '=STATUS' => Export\Run\Steps\Promo::STORAGE_STATUS_SUCCESS ],
			'select' => [ 'COLLECTION_ID', 'SETUP_ID' ],
			'group' => [ 'COLLECTION_ID', 'SETUP_ID' ],
		]);

		return Utils\ArrayHelper::groupBy($query->fetchAll(), 'COLLECTION_ID');
	}

	protected function linked($entityId)
	{
		$query = Export\Collection\Internals\SetupLinkTable::getList([
			'filter' => [ '=COLLECTION_ID' => $entityId ],
			'select' => [ 'SETUP_ID' ],
		]);

		return array_column($query->fetchAll(), 'SETUP_ID', 'SETUP_ID');
	}
}