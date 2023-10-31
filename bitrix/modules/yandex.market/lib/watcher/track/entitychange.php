<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market\Data\Type;
use Yandex\Market\Glossary;
use Yandex\Market\Export;
use Yandex\Market\SalesBoost;
use Yandex\Market\Watcher;
use Yandex\Market\Reference\Agent;

class EntityChange extends Agent\Base
{
	public static function schedule($service, $entity, $id, Main\Type\Date $nextDate)
	{
		if ($nextDate instanceof Type\CanonicalDateTime)
		{
			$nextDate = clone $nextDate;
			$nextDate->setServerTimeZone();
		}

		static::register([
			'method' => 'fire',
			'arguments' => [ $service, $entity, (int)$id ],
			'next_exec' => $nextDate->toString(),
			'update' => Agent\Controller::UPDATE_RULE_STRICT,
		]);
	}

	public static function release($service, $entity, $id)
	{
		static::unregister([
			'method' => 'fire',
			'arguments' => [ $service, $entity, (int)$id ],
		]);
	}

	public static function fire($service, $entity, $id)
	{
		global $pPERIOD;

		$isNeedRepeat = false;

		ElementChange::add($entity, $id, $id);

		$model = static::model($service, $entity, $id);

		if ($model === null) { return false; }

		$nextDate = $model->getNextActiveDate();

		$model->updateListener();

		if ($nextDate !== null)
		{
			$isNeedRepeat = true;
			$pPERIOD = $nextDate->getTimestamp() - time();
		}

		return $isNeedRepeat;
	}

	/** @return Watcher\Agent\EntityWithActiveDates|null */
	protected static function model($service, $type, $id)
	{
		try
		{
			$classMap = [
				Glossary::SERVICE_EXPORT => [
					Export\Glossary::ENTITY_PROMO => Export\Promo\Model::class,
					Export\Glossary::ENTITY_COLLECTION => Export\Collection\Model::class,
				],
				Glossary::SERVICE_SALES_BOOST => [
					Glossary::SERVICE_SALES_BOOST => SalesBoost\Setup\Model::class,
				],
			];

			if (!isset($classMap[$service]))
			{
				throw new Main\ArgumentException(sprintf('unknown %s service', $service));
			}

			if (!isset($classMap[$service][$type]))
			{
				throw new Main\ArgumentException(sprintf('unknown %s type for %s', $type, $service));
			}

			$className = $classMap[$service][$type];

			$result = $className::loadById($id);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = null;
		}

		return $result;
	}
}