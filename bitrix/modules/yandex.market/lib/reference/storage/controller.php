<?php

namespace Yandex\Market\Reference\Storage;

use Bitrix\Main;
use Yandex\Market\Config;

class Controller
{
	public static function createTable($classList = null)
	{
		$className = Table::getClassName();

		if ($classList === null)
		{
			$classList = static::getClassList($className);
		}

		/** @var Table $className */
		foreach ($classList as $className)
		{
			if (is_subclass_of($className, TableDeprecated::class)) { continue; }

			$entity = $className::getEntity();
			$connection = $entity->getConnection();
			$tableName = $entity->getDBTableName();

			if ($connection->isTableExists($tableName))
			{
				$className::migrate($connection);
				$connection->clearCaches();
			}
			else
			{
				$entity->createDbTable();
				$className::createIndexes($connection);
			}
		}
	}

	public static function dropTable($classList = null)
	{
		$className = Table::getClassName();
		$dropped = [];

		if ($classList === null)
		{
			$classList = static::getClassList($className);
		}

		/** @var Table $className */
		foreach ($classList as $className)
		{
			$tableName = $className::getTableName();

			if (isset($dropped[$tableName])) { continue; }

			$entity = $className::getEntity();
			static::internalDropTable($entity);

			$dropped[$tableName] = true;
		}
	}

	/**
	 * ���������� ������ ���� ���������� ���������� Event,
	 * ������������ ��� ����������� ���� ������������ � updateRegular().
	 *
	 * @param string �������� ������, ���������� �������� ���� �������
	 *
	 * @return array ������ ��� ������� ��� ������
	 * */
	protected static function getClassList($baseClassName)
	{
		$baseDir = Config::getModulePath();
		$baseNamespace = Config::getNamespace();
		$directory = new \RecursiveDirectoryIterator($baseDir);
		$iterator = new \RecursiveIteratorIterator($directory);
		$result = [];

		/** @var \DirectoryIterator $entry */
		foreach ($iterator as $entry)
		{
			if (
				$entry->isFile()
				&& $entry->getExtension() === 'php'
			)
			{
				$relativePath = str_replace($baseDir, '', $entry->getPath());
				$namespace = $baseNamespace . str_replace('/', '\\', $relativePath) . '\\';
				$className = $entry->getBasename('.php');

				if ($className !== 'table')
				{
					$className .= 'Table';
				}

				$fullClassName = $namespace . $className;

				if (
					class_exists($fullClassName)
					&& is_subclass_of($fullClassName, $baseClassName)
				)
				{
					$result[] = $fullClassName;
				}
			}
		}

		return $result;
	}

	protected static function internalDropTable(Main\Entity\Base $entity)
	{
		$connection = $entity->getConnection();
		$tableName = $entity->getDBTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->dropTable($tableName);
		}
	}
}
