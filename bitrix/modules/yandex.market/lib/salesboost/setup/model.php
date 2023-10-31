<?php
/**
 * @noinspection PhpIncompatibleReturnTypeInspection
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
namespace Yandex\Market\SalesBoost\Setup;

use Bitrix\Main;
use Yandex\Market\Config;
use Yandex\Market\Data;
use Yandex\Market\Reference;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;
use Yandex\Market\Utils;
use Yandex\Market\Watcher;
use Yandex\Market\Glossary;

class Model extends Reference\Storage\Model
	implements
		Watcher\Agent\EntityRefreshable,
		Watcher\Agent\EntityWithActiveDates
{
	use Reference\Concerns\HasOnce;
	use Watcher\Agent\EntityRefreshableTrait;

	public static function getDataClass()
	{
		return Table::class;
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

		if ($startDate && $startDate->getTimestamp() > $now)
		{
			$result = false;
		}
		else if ($finishDate && $finishDate->getTimestamp() <= $now)
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
		$startDate = $this->getField('FINISH_DATE');

		return $startDate instanceof Main\Type\DateTime ? $startDate : null;
	}

	public function getNextActiveDate($now = null)
	{
		if ($now === null) { $now = time(); }

		$result = null;
		$resultTimestamp = null;
		$dates = [
			$this->getStartDate(),
			$this->getFinishDate(),
		];

		foreach ($dates as $date)
		{
			if (!($date instanceof Main\Type\Date)) { continue; }

			$dateTimestamp = $date->getTimestamp();

			if ($now < $dateTimestamp && ($resultTimestamp === null || $dateTimestamp < $resultTimestamp))
			{
				$resultTimestamp = $dateTimestamp;
				$result = $date;
			}
		}

		return $result;
	}

	/** @return Trading\Business\Model */
	public function getBusiness()
	{
		return $this->once('getBusiness', $this->getField('BUSINESS_ID'), function($businessId) {
			return Trading\Business\Model::loadById($businessId);
		});
	}

	/** @return Trading\Setup\Model */
	public function getTrading()
	{
		return $this->getBusiness()->getPrimaryTrading();
	}

	public function getSort()
	{
		$sort = Data\Number::normalize($this->getField('SORT'));

		if ($sort === null) { $sort = 500; }

		return $sort;
	}

	public function getBidDefault()
	{
		return Data\Number::normalize($this->getField('BID_DEFAULT'));
	}

	public function getBidFormat()
	{
		return $this->getField('BID_FORMAT');
	}

	public function getBidFields()
	{
		$value = $this->getField('BID_FIELD');

		if (!is_array($value)) { return []; }

		$result = [];

		foreach ($value as $item)
		{
			if (empty($item)) { continue; }

			list($source, $field) = explode(':', $item, 2);

			if (empty($source) || empty($field)) { continue; }

			$result[] = [
				'SOURCE' => $source,
				'FIELD' => $field,
			];
		}

		return $result;
	}

	public function onBeforeRemove()
	{
		$this->handleChanges(false);
		$this->handleRefresh(false);
		$this->handleActiveDate(false);
	}

	public function onAfterSave()
	{
		$this->updateListener();
	}

	public function updateListener()
	{
		$this->handleRefresh();
		$this->handleChanges();
		$this->handleActiveDate();
	}

	public function hasFullRefresh()
	{
		return (
			$this->getRefreshPeriod() !== null
			&& $this->isActive()
			&& $this->isActiveDate()
		);
	}

	public function handleRefresh($direction = null)
	{
		$agentParams = [
			'method' => 'refreshStart',
			'arguments' => [ (int)$this->getId() ],
		];

		if ($direction === null) { $direction = $this->hasFullRefresh(); }

		if ($direction)
		{
			$nextExecDate = $this->getRefreshNextExec();

			$agentParams['interval'] = $this->getRefreshPeriod();
			$agentParams['next_exec'] = ConvertTimeStamp($nextExecDate->getTimestamp(), 'FULL');

			SalesBoost\Run\Agent::register($agentParams);
		}
		else
		{
			SalesBoost\Run\Agent::unregister($agentParams);
			SalesBoost\Run\Agent::unregister([
				'method' => 'refresh',
				'arguments' => [ (int)$this->getId() ],
				'search' => Reference\Agent\Controller::SEARCH_RULE_SOFT,
			]);

			Watcher\Agent\StateFacade::drop('refresh', Glossary::SERVICE_SALES_BOOST, $this->getId());
		}
	}

	public function handleChanges($direction = null)
	{
		$installer = new Watcher\Track\Installer(Glossary::SERVICE_SALES_BOOST, Glossary::ENTITY_SETUP, $this->getId());

		if ($direction === null) { $direction = $this->isAutoUpdate(); }

		if (!$direction)
		{
			$installer->uninstall();
		}
		else
		{
			if ($this->isActiveDate())
			{
				$sources = $this->getTrackSourceList();
				$entities = $this->getBindEntities();

				if (empty($sources)) { $entities = []; }
			}
			else
			{
				$sources = [];
				$entities = [];
			}

			if ($this->getStartDate() !== null || $this->getFinishDate() !== null)
			{
				$entities[] = new Watcher\Track\BindEntity(Glossary::SERVICE_SALES_BOOST, $this->getId());
			}

			$installer->install($sources, $entities);
		}
	}

	public function getTrackSourceList()
	{
		$partials = [];

		/** @var SalesBoost\Product\Model $product */
		foreach ($this->getProductCollection() as $product)
		{
			$partials[] = $product->getTrackSourceList();
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	public function getBindEntities()
	{
		$result = [];

		/** @var SalesBoost\Product\Model $product */
		foreach ($this->getProductCollection() as $product)
		{
			if ($product->getFilterCollection()->count() === 0) { continue; }

			$context = $product->getContext();

			$result[] = new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['IBLOCK_ID']);

			if ($context['HAS_OFFER'])
			{
				$result[] = new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['OFFER_IBLOCK_ID']);
			}
		}

		return $result;
	}

	public function handleActiveDate($direction = null)
	{
		$nextDate = $this->getNextActiveDate();

		if ($direction === null) { $direction = $this->isAutoUpdate(); }

		if ($direction && $nextDate)
		{
			Watcher\Track\EntityChange::schedule(Glossary::SERVICE_SALES_BOOST, Glossary::SERVICE_SALES_BOOST, $this->getId(), $nextDate);
		}
		else
		{
			Watcher\Track\EntityChange::release(Glossary::SERVICE_SALES_BOOST, Glossary::SERVICE_SALES_BOOST, $this->getId());
		}
	}

	public function isAutoUpdate()
	{
		return (
			$this->isActive()
			&& Config::getOption('sales_boost_auto_update', 'Y') === 'Y'
		);
	}

	public function getRefreshPeriod()
	{
		$default = Utils::isAgentUseCron() ? 86400 : 0;
		$period = (int)Config::getOption('sales_boost_refresh_period', $default);

		return $period > 0 ? $period : null;
	}

	public function getRefreshTime()
	{
		return Data\Time::parse(Config::getOption('sales_boost_refresh_time'));
	}

	/** @return SalesBoost\Product\Collection */
	public function getProductCollection()
	{
		return $this->getChildCollection('SALES_BOOST_PRODUCT');
	}

	public function getContext()
	{
		return [
			'SALES_BOOST_ID' => $this->getId(),
			'BUSINESS_ID' => $this->getField('BUSINESS_ID'),
		];
	}

	protected function getChildCollectionReference($fieldKey)
	{
		if ($fieldKey === 'SALES_BOOST_PRODUCT')
		{
			return SalesBoost\Product\Collection::class;
		}

		return null;
	}
}