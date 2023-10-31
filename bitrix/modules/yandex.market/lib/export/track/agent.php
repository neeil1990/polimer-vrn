<?php
namespace Yandex\Market\Export\Track;

use Yandex\Market\Glossary;
use Yandex\Market\Watcher;
use Yandex\Market\Reference;

/** @deprecated */
class Agent extends Reference\Agent\Base
{
    public static function entityChange($entityType, $entityId)
    {
		return Watcher\Track\EntityChange::fire(Glossary::SERVICE_EXPORT, $entityType, $entityId);
    }
}