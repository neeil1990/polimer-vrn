<?php

namespace Yandex\Market\Ui\Admin;

use Yandex\Market;
use Bitrix\Main;

class Menu extends Market\Reference\Event\Regular
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnBuildGlobalMenu',
			],
		];
	}

	public static function onBuildGlobalMenu(&$globalMenu, &$moduleMenu)
	{
		$menuIndexes = static::searchModuleSections($moduleMenu, 'yamarket_');

		if (!empty($menuIndexes))
		{
			$globalSection = static::getGlobalSection();
			$globalSectionId = $globalSection['items_id'];

			static::moveMenuToParent($moduleMenu, $menuIndexes, $globalSectionId);

			$globalMenu[$globalSectionId] = $globalSection;
		}
	}

	protected static function getGlobalSection()
	{
		return [
			'menu_id' => 'yamarket',
			'text' => static::getLang('UI_ADMIN_MENU_TEXT'),
			'title' => static::getLang('UI_ADMIN_MENU_TITLE'),
			'sort' => 310,
			'items_id' => 'global_menu_yamarket',
			'items' => []
		];
	}

	protected static function searchModuleSections($moduleMenu, $prefix)
	{
		$result = [];

		foreach ($moduleMenu as $menuIndex => $menu)
		{
			if (
				isset($menu['section'])
				&& is_string($menu['section'])
				&& Market\Data\TextString::getPosition($menu['section'], $prefix) === 0
			)
			{
				$result[] = $menuIndex;
			}
		}

		return $result;
	}

	protected static function moveMenuToParent(&$moduleMenu, $indexes, $parentName)
	{
		foreach ($indexes as $index)
		{
			$moduleMenu[$index]['parent_menu'] = $parentName;
		}
	}
}