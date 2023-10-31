<?php
/** @noinspection PhpUnusedParameterInspection */
namespace Yandex\Market\Export\Track;

use Yandex\Market\Watcher\Track\ElementChange;

/** @deprecated */
class Manager
{
    public static function isElementChangeRegistered($elementType, $elementId, $sourceType = null, $sourceParams = null)
    {
		return ElementChange::has($elementType, $elementId);
    }

    public static function registerEntityChange($entityType, $entityId)
    {
	    ElementChange::add($entityType, $entityId);
    }

    public static function registerElementChange($elementType, $elementId, $sourceType = null, $sourceParams = null)
    {
	    ElementChange::add($elementType, $elementId);
    }
}