<?php
namespace Yandex\Market\Export\Track;

use Yandex\Market\Watcher;
use Yandex\Market\Glossary;

/** @deprecated */
class Registry
{
	public static function addEntitySources($entityType, $entityId, $sourceList)
    {
		$installer = new Watcher\Track\SourceInstaller(Glossary::SERVICE_EXPORT, $entityType, $entityId);
	    $installer->install($sourceList);
    }

    public static function removeEntitySources($entityType, $entityId)
    {
	    $installer = new Watcher\Track\SourceInstaller(Glossary::SERVICE_EXPORT, $entityType, $entityId);
	    $installer->uninstall();
    }

    public static function getEntitySources($entityType, $entityId)
    {
	    return [];
    }

    public static function getTypeSources($typeList)
    {
	    return [];
    }
}