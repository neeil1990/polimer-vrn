<?php

namespace Yandex\Market\Export\Run\Writer;

use Yandex\Market;

class IndexFacade
{
	public static function isAllowed()
	{
		return (Market\Config::getOption('export_writer_index', 'Y') === 'Y');
	}

	public static function resolve($setupId, $fileName)
	{
		$allowed = static::isAllowed();
		$found = static::search($setupId, $fileName);

		if ($allowed === $found) { return false; }

		if ($allowed)
		{
            static::remove($setupId);
			static::create($setupId, $fileName);
		}
		else
		{
			static::remove($setupId);
		}

		return true;
	}

	public static function create($setupId, $fileName)
	{
		$saveResult = FileIndex\RegistryTable::add([
            'SETUP_ID' => $setupId,
            'FILE_NAME' => $fileName,
			'FILE_SIZE' => 0,
        ]);

		Market\Result\Facade::handleException($saveResult);

		return $saveResult->getId();
	}

	public static function remove($setupId)
	{
		$positionResult = FileIndex\PositionTable::deleteBatch([
			'filter' => [ '=SETUP_ID' => $setupId ],
		]);
		$registryResult = FileIndex\RegistryTable::deleteBatch([
            'filter' => [ '=SETUP_ID' => $setupId ],
        ]);

		Market\Result\Facade::handleException($positionResult);
		Market\Result\Facade::handleException($registryResult);
	}

	public static function search($setupId, $fileName = null)
	{
        $filter = [
            '=SETUP_ID' => $setupId,
        ];

        if ($fileName !== null)
        {
            $filter['=FILE_NAME'] = $fileName;
        }

		$query = FileIndex\RegistryTable::getList([
			'filter' => $filter,
			'select' => [ 'SETUP_ID' ],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	public static function reset($setupId)
	{
		static::resetByFilter([
			'filter' => [ '=SETUP_ID' => $setupId ],
		]);
	}

	public static function resetAll()
	{
		static::resetByFilter([]);
	}

	protected static function resetByFilter(array $parameters)
	{
		$deleteResult = FileIndex\RegistryTable::deleteBatch($parameters);
		// leave PositionTable for parallel agent execution

		Market\Result\Facade::handleException($deleteResult);
	}
}