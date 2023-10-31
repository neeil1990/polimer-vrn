<?php

namespace Yandex\Market\Export\Setup\Internals;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class GroupTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_setup_group';
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_SETUP_GROUP';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
			]),
			new Main\Entity\EnumField('UI_SERVICE', [
				'values' => array_merge(
					Market\Ui\Service\Manager::getTypes(),
					[ Market\Ui\Service\Manager::TYPE_COMMON ]
				),
				'default_value' => Market\Ui\Service\Manager::TYPE_COMMON,
			]),
			new Main\Entity\IntegerField('PARENT_ID', [
				'default_value' => 0,
				'validation' => [__CLASS__, 'getValidationForParentId'],
			]),
			new Main\Entity\ReferenceField('PARENT', static::class, [
				'=this.PARENT_ID' => 'ref.ID',
			]),
			new Main\Entity\ReferenceField('SETUP_LINK', GroupLinkTable::class, [
				'=this.ID' => 'ref.GROUP_ID',
			]),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::class, [
				'=this.SETUP_LINK.SETUP_ID' => 'ref.ID',
			]),
		];
	}

	public static function getValidationForParentId()
	{
		return [
			[ static::class, 'validateParentId' ],
		];
	}

	public static function validateParentId($value, $primary, $row, $field)
	{
		$value = (int)$value;
		$primaryId = static::getPrimaryId($primary);
		$result = true;

		if ($primaryId > 0)
		{
			if ($primaryId === $value)
			{
				$result = Market\Config::getLang('EXPORT_SETUP_INTERNALS_GROUP_ENTITY_PARENT_ID_VALIDATE_MATCH_SELF');
			}
			else
			{
				$service = !empty($row['UI_SERVICE']) ? $row['UI_SERVICE'] : Market\Ui\Service\Manager::TYPE_COMMON;
				$tree = static::getTree([
					'filter' => [ '=UI_SERVICE' => $service ],
				]);

				if (static::isGroupInside($tree, $primaryId, $value))
				{
					$result = Market\Config::getLang('EXPORT_SETUP_INTERNALS_GROUP_ENTITY_PARENT_ID_VALIDATE_INSIDE_CHILDREN');
				}
			}
		}

		return $result;
	}

	protected static function isGroupInside($tree, $parentId, $searchId)
	{
		$parentDepth = null;
		$result = false;

		foreach ($tree as $group)
		{
			$groupId = (int)$group['ID'];

			if ($groupId === $parentId)
			{
				$parentDepth = $group['DEPTH_LEVEL'];
			}
			else if ($parentDepth !== null && $group['DEPTH_LEVEL'] <= $parentDepth)
			{
				$parentDepth = null;
				break;
			}

			if ($searchId === $groupId)
			{
				$result = ($parentDepth !== null);
				break;
			}
		}

		return $result;
	}

	public static function getTree(array $parameters = [])
	{
		$rows = static::getList($parameters)->fetchAll();

		return static::sortTreeRows($rows);
	}

	protected static function sortTreeRows($rows, $iterationGroup = 0, $depthLevel = 1)
	{
		$result = [];

		foreach ($rows as $row)
		{
			$parentId = (int)$row['PARENT_ID'];

			if ($parentId === $iterationGroup)
			{
				// add self

				$result[] = $row + [ 'DEPTH_LEVEL' => $depthLevel ];

				// insert children

				$children = static::sortTreeRows($rows, (int)$row['ID'], $depthLevel + 1);

				if (!empty($children))
				{
					array_push($result, ...$children);
				}
			}
		}

		return $result;
	}

	protected static function deleteReference($primary)
	{
		parent::deleteReference($primary);
		static::deleteReferenceGroupLink($primary);
	}

	protected static function deleteReferenceGroupLink($primary)
	{
		GroupLinkTable::deleteBatch([
			'filter' => [ '=GROUP_ID' => $primary ],
		]);
	}
}
