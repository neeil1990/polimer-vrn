<?php
/**
 * @noinspection PhpIncompatibleReturnTypeInspection
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
namespace Yandex\Market\Export\Collection;

use Bitrix\Main;
use Yandex\Market\Data as GlobalData;
use Yandex\Market\Export\Glossary;
use Yandex\Market\Reference;
use Yandex\Market\Export;
use Yandex\Market\Watcher;

class Model extends Reference\Storage\Model
	implements
		Export\Run\Data\EntityExportable,
		Watcher\Agent\EntityWithActiveDates
{
	use Reference\Concerns\HasOnce;

	public static function getDataClass()
	{
		return Table::class;
	}

	public function onBeforeRemove()
	{
		$this->handleChanges(false);
		$this->handleActiveDate(false);
	}

	public function onAfterSave()
	{
		$this->updateListener();
	}
	
	public function getTagDescriptionList()
	{
		return [
			[
				'TAG' => 'collection',
				'VALUE' => null,
				'ATTRIBUTES' => [
					'id' => [ 'TYPE' => 'COLLECTION', 'FIELD' => 'PRIMARY' ],
				]
			],
			[
				'TAG' => 'url',
				'VALUE' => [ 'TYPE' => 'COLLECTION', 'FIELD' => 'URL' ],
				'ATTRIBUTES' => []
			],
			[
				'TAG' => 'name',
				'VALUE' => [ 'TYPE' => 'COLLECTION', 'FIELD' => 'NAME' ],
				'ATTRIBUTES' => []
			],
			[
				'TAG' => 'picture',
				'VALUE' => [ 'TYPE' => 'COLLECTION', 'FIELD' => 'PICTURE' ],
				'ATTRIBUTES' => []
			],
			[
				'TAG' => 'description',
				'VALUE' => [ 'TYPE' => 'COLLECTION', 'FIELD' => 'DESCRIPTION' ],
				'ATTRIBUTES' => []
			],
		];
	}

	public function getName()
	{
		return $this->getField('NAME');
	}

	public function isActive()
	{
		return ((string)$this->getField('ACTIVE') === Table::BOOLEAN_Y);
	}
	
	public function isActiveDate()
	{
		$startDate = $this->getStartDate();
		$finishDate = $this->getFinishDate();
		$now = time();
		$result = true;

		if ($startDate !== null && $startDate->getTimestamp() > $now)
		{
			$result = false;
		}
		else if ($finishDate !== null && $finishDate->getTimestamp() <= $now)
		{
			$result = false;
		}

		return $result;
	}

	public function getStartDate()
	{
		$startDate = $this->getField('START_DATE');

		return $startDate instanceof Main\Type\DateTime ? $startDate : null;
	}

	public function getFinishDate()
	{
		$finishDate = $this->getField('FINISH_DATE');

		return $finishDate instanceof Main\Type\DateTime ? $finishDate : null;
	}

	public function getNextActiveDate()
	{
		$result = null;
		$now = new Main\Type\DateTime();
		$dates = [
			$this->getStartDate(),
			$this->getFinishDate(),
		];

		foreach ($dates as $date)
		{
			if ($date !== null && GlobalData\DateTime::compare($date, $now) !== -1)
			{
				$result = $date;
				break;
			}
		}

		return $result;
	}

	public function isExportForAll()
	{
		return ((string)$this->getField('SETUP_EXPORT_ALL') === Table::BOOLEAN_Y);
	}

	public function updateListener()
	{
		$this->handleChanges();
		$this->handleActiveDate();
	}

	public function handleChanges($direction = null)
	{
		if ($direction === null) { $direction = $this->isActive(); }

		$installer = new Watcher\Track\Installer(Glossary::SERVICE_SELF, Glossary::ENTITY_COLLECTION, $this->getId());

		if ($direction)
		{
			$installer->install([], $this->getBindEntities());
		}
		else
		{
			$installer->uninstall();
		}
	}

	public function isListenActiveDate()
	{
		return ($this->isActive() && $this->getNextActiveDate() !== null && $this->hasAutoUpdateSetup());
	}

	public function hasAutoUpdateSetup()
	{
		$result = false;

		/** @var Export\Setup\Model $setup */
		foreach ($this->getSetupCollection() as $setup)
		{
			if ($setup->isAutoUpdate() && $setup->isFileReady())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function handleActiveDate($direction = null)
	{
		$nextDate = $this->getNextActiveDate();

		if ($direction === null) { $direction = $this->isListenActiveDate(); }

		if ($direction && $nextDate)
		{
			Watcher\Track\EntityChange::schedule(Glossary::SERVICE_SELF, Glossary::ENTITY_COLLECTION, $this->getId(), $nextDate);
		}
		else
		{
			Watcher\Track\EntityChange::release(Glossary::SERVICE_SELF, Glossary::ENTITY_COLLECTION, $this->getId());
		}
	}

	public function getBindEntities()
	{
		if ($this->getStartDate() === null && $this->getFinishDate() === null) { return []; }

		$result = [];

		/** @var Export\Setup\Model $setup */
		foreach ($this->getSetupCollection() as $setup)
		{
			if (!$setup->isAutoUpdate() || !$setup->isFileReady()) { continue; }

			$result[] = new Watcher\Track\BindEntity(Glossary::ENTITY_COLLECTION, $this->getId(), null, $setup->getId());
		}

		return $result;
	}

	/** @return Limit */
	public function getLimit()
	{
		return $this->once('getLimit', null, function() {
			return new Limit((array)$this->getField('LIMIT_SETTINGS'));
		});
	}

	/** @return Strategy\Strategy */
	public function getStrategy()
	{
		return $this->once('getStrategy', null, function() {
			$type = $this->getField('STRATEGY');
			$strategy = Strategy\Registry::createStrategy($type);
			$strategy->setValues((array)$this->getField('STRATEGY_SETTINGS') + [
				'COLLECTION_ID' => $this->getId(),
				'NAME' => $this->getField('NAME'),
			]);

			if ($strategy instanceof Export\Collection\Strategy\StrategyFilterable)
			{
				$strategy->setProductCollection($this->getProductCollection());
			}

			return $strategy;
		});
	}

	protected function isBuiltInStrategy()
	{
		return ($this->getField('STRATEGY') === Strategy\Registry::PRODUCT_FILTER);
	}

	public function makeCollectionSign(Data\FeedCollection $feedCollection)
	{
		if ($this->isBuiltInStrategy())
		{
			return $this->getId();
		}

		return $feedCollection->getId() . '-' . $this->getId();
	}

	public function makeCollectionPrimary(Data\FeedCollection $feedCollection)
	{
		if ($this->isBuiltInStrategy())
		{
			return $this->getId();
		}

		return $feedCollection->getPrimary() . '-' . $this->getId();
	}

	/** @return Export\CollectionProduct\Collection */
	public function getProductCollection()
	{
		return $this->getChildCollection('COLLECTION_PRODUCT');
	}

	/** @return Export\Setup\Collection */
	public function getSetupCollection()
	{
		return $this->getChildCollection('SETUP');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		if ($fieldKey === 'SETUP')
		{
			return Export\Setup\Collection::class;
		}

		if ($fieldKey === 'COLLECTION_PRODUCT')
		{
			return Export\CollectionProduct\Collection::class;
		}

		return null;
	}

	protected function getChildCollectionQueryParameters($fieldKey)
	{
		if ($fieldKey === 'SETUP')
		{
			if ($this->isExportForAll()) { return []; }

			return [
				'filter' => [ '=COLLECTION_LINK.COLLECTION_ID' => $this->getId() ],
			];
		}

		return parent::getChildCollectionQueryParameters($fieldKey);
	}
}