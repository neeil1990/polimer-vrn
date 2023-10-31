<?php

namespace Yandex\Market\Data;

use Bitrix\Main;
use Yandex\Market;

class UserGroup
{
	use Market\Reference\Concerns\HasOnceStatic;

	protected static $userGroupsCache = [];

	public static function getUserGroups($userId)
	{
		$userId = (int)$userId;

		if (!isset(static::$userGroupsCache[$userId]))
		{
			static::$userGroupsCache[$userId] = static::loadUserGroups($userId);
		}

		return static::$userGroupsCache[$userId];
	}

	protected static function loadUserGroups($userId)
	{
		return Main\UserTable::getUserGroupIds($userId);
	}

	public static function getDefaults()
	{
		return static::onceStatic('loadDefaults');
	}

	protected static function loadDefaults()
	{
		$result = Main\UserTable::getUserGroupIds(-1); // bitrix16.0 bug with 0

		Main\Type\Collection::normalizeArrayValuesByInt($result);

		return $result;
	}

	public static function getEnum()
	{
		return static::onceStatic('loadEnum');
	}

	protected static function loadEnum()
	{
		$result = [];

		$query = Main\GroupTable::getList([
			'filter' => [ '=ACTIVE' => 'Y' ],
			'select' => [ 'ID', 'NAME' ],
			'order' => [ 'C_SORT' => 'ASC', 'ID' => 'ASC' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => (int)$row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
			];
		}

		return $result;
	}

	public static function extendGroup($groupId)
	{
		$groupId = (int)$groupId;
		$result = static::getDefaults();

		if ($groupId > 0 && !in_array($groupId, $result, true))
		{
			$result[] = $groupId;
		}

		return $result;
	}
}