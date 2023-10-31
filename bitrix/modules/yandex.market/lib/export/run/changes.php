<?php
/** @noinspection PhpUnusedParameterInspection */
namespace Yandex\Market\Export\Run;

use Bitrix\Main;
use Yandex\Market\Glossary;
use Yandex\Market\Watcher\Track;

/** @deprecated */
class Changes
{
	public static function register($setupId, $entityType, $entityId)
	{
		Track\ElementChange::add($entityType, $entityId);
	}

	public static function releaseAll($setupId)
	{
		Track\StampFacade::shift(Glossary::SERVICE_EXPORT, $setupId);
	}

	public static function release($setupId, Main\Type\DateTime $dateTime)
	{
		static::releaseAll($setupId);
	}

	public static function flush()
	{
		Track\ElementChange::flush();
	}
}