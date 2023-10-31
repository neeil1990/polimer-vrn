<?php

namespace Yandex\Market\Export\IblockLink;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_iblocklink';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'SETUP_ID' ]);
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_IBLOCKLINK';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true
			]),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			]),
			new Main\Entity\IntegerField('IBLOCK_ID', [
				'required' => true
			]),
			new Main\Entity\ReferenceField('IBLOCK_LINK', 'Bitrix\Iblock\Iblock', [
				'=this.IBLOCK_ID' => 'ref.ID'
			]),
			new Main\Entity\StringField('SALES_NOTES'),
			new Main\Entity\BooleanField('EXPORT_ALL', [
				'values' => [ '0', '1' ],
				'default_value' => '1',
			]),
			new Main\Entity\ReferenceField('DELIVERY', Market\Export\Delivery\Table::getClassName(), [
				'=this.ID' => 'ref.ENTITY_ID',
				'=ref.ENTITY_TYPE' => [ '?', Market\Export\Delivery\Table::ENTITY_TYPE_IBLOCK_LINK ]
			]),
			new Main\Entity\ReferenceField('FILTER', Market\Export\Filter\Table::getClassName(), [
				'=ref.ENTITY_TYPE' => [ '?', Market\Export\Filter\Table::ENTITY_TYPE_IBLOCK_LINK ],
				'=ref.ENTITY_ID' => 'this.ID'
			]),
			new Main\Entity\ReferenceField('PARAM', Market\Export\Param\Table::getClassName(), [
				'=this.ID' => 'ref.IBLOCK_LINK_ID'
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'FILTER' => [
				'TABLE' => Market\Export\Filter\Table::getClassName(),
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Market\Export\Filter\Table::ENTITY_TYPE_IBLOCK_LINK,
					'ENTITY_ID' => $primary
				],
				'ORDER' => [
					'SORT' => 'asc',
					'ID' => 'asc'
				]
			],
			'PARAM' => [
				'TABLE' => Market\Export\Param\Table::getClassName(),
				'LINK_FIELD' => 'IBLOCK_LINK_ID',
				'LINK' => [
					'IBLOCK_LINK_ID' => $primary,
					'PARENT_ID' => 0,
				],
			],
			'DELIVERY' => [
				'TABLE' => Market\Export\Delivery\Table::getClassName(),
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Market\Export\Delivery\Table::ENTITY_TYPE_IBLOCK_LINK,
					'ENTITY_ID' => $primary
				]
			]
		];
	}

	public static function loadExternalReference($primaryList, $select = null, $isCopy = false)
	{
		$parts = [];

		if (empty($select) || in_array('PARAM', $select, true))
		{
			$parts[] = static::loadParamReference($primaryList, $isCopy);

			if (empty($select))
			{
				$references = static::getReference($primaryList);
				$select = array_keys($references);
			}

			$select = array_diff($select, [ 'PARAM' ]);
		}

		$parts[] = parent::loadExternalReference($primaryList, $select, $isCopy);

		return static::mergeReferenceParts($parts);
	}

	/* optimized load for reference PARAM */
	protected static function loadParamReference($primaryList, $isCopy = false)
	{
		if (empty($primaryList)) { return []; }

		// load rows

		$query = Market\Export\Param\Table::getList([
			'filter' => [ '=IBLOCK_LINK_ID' => $primaryList ],
			'order' => [ 'PARENT_ID' => 'asc' ],
		]);

		$rows = $query->fetchAll();

		if (empty($rows)) { return []; }

		$ids = array_column($rows, 'ID');
		$rows = array_combine($ids, $rows);

		// load external

		$externalDataList = Market\Export\Param\Table::loadExternalReference($ids, [ 'PARAM_VALUE' ], $isCopy);

		foreach ($externalDataList as $id => $externalData)
		{
			$rows[$id] += $externalData;
		}

		// group by parent

		$parentValues = [];

		foreach ($rows as $row)
		{
			$parentId = $row['IBLOCK_LINK_ID'];

			if (!isset($parentValues[$parentId]))
			{
				$parentValues[$parentId] = [
					'PARAM' => [],
				];
			}

			$parentValues[$parentId]['PARAM'][] = $row;
		}

		// convert to tree

		foreach ($parentValues as &$parentValue)
		{
			$parentValue['PARAM'] = static::convertParamReferenceToTree($parentValue['PARAM']);

			if ($isCopy)
			{
				$parentValue['PARAM'] = static::clearParamReferenceCopy($parentValue['PARAM']);
			}
		}
		unset($parentValue);

		return $parentValues;
	}

	protected static function convertParamReferenceToTree(array $paramRows)
	{
		$valueTreeMap = [];
		$result = [];

		foreach ($paramRows as $paramRow)
		{
			$valueId = (int)$paramRow['ID'];
			$parentLevel = &$result;

			if (empty($paramRow['PARENT_ID'])) // is root
			{
				$parentTree = [];
			}
			else
			{
				if (!isset($valueTreeMap[$paramRow['PARENT_ID']])) { continue; }

				$parentTree = $valueTreeMap[$paramRow['PARENT_ID']];
			}

			foreach ($parentTree as $parentId)
			{
				if (!isset($parentLevel[$parentId]))
				{
					$parentLevel = null;
					break;
				}

				if (!isset($parentLevel[$parentId]['CHILDREN']))
				{
					$parentLevel[$parentId]['CHILDREN'] = [];
				}

				$parentLevel = &$parentLevel[$parentId]['CHILDREN'];
			}

			if ($parentLevel === null) { continue; }

			$parentLevel[$valueId] = $paramRow;

			$selfTree = $parentTree;
			$selfTree[] = $valueId;

			$valueTreeMap[$valueId] = $selfTree;

			unset($parentLevel);
		}

		return $result;
	}

	protected static function clearParamReferenceCopy(array $paramRows)
	{
		foreach ($paramRows as &$paramRow)
		{
			if (!empty($paramRow['CHILDREN']))
			{
				$paramRow['CHILDREN'] = static::clearParamReferenceCopy($paramRow['CHILDREN']);
			}

			$paramRow = array_diff_key($paramRow, [
				'IBLOCK_LINK_ID' => true,
				'ID' => true,
				'PARENT_ID' => true,
			]);
		}
		unset($paramRow);

		return $paramRows;
	}

	protected static function mergeReferenceParts(array $parts)
	{
		if (count($parts) === 1) { return reset($parts); }

		$result = array_pop($parts);

		foreach ($parts as $part)
		{
			foreach ($part as $key => $values)
			{
				if (!isset($result[$key]))
				{
					$result[$key] = $values;
				}
				else
				{
					$result[$key] += $values;
				}
			}
		}

		return $result;
	}
}