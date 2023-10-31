<?php
namespace Yandex\Market\Ui\UserField;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui;
use Yandex\Market\Utils;
use Bitrix\Main;
use Bitrix\Iblock;

/** @noinspection PhpUnused */
class IblockSectionType extends EnumerationType
{
	use Concerns\HasMessage;
	use Concerns\HasOnceStatic;

	const ALL = 'all';

	protected static $variants = [];

	public static function GetList($arUserField)
	{
		$variants = static::getVariants($arUserField);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	protected static function getVariants($userField)
	{
		try
		{
			static::loadModule();

			$result = [];

			foreach (static::iblockIds($userField) as $iblockId)
			{
				$group = \CIBlock::GetArrayByID($iblockId, 'NAME');

				$result[] = [
					'ID' => static::ALL . $iblockId,
					'VALUE' => self::getMessage('ALL'),
					'GROUP' => $group,
				];

				foreach (static::iblockSections($iblockId) as $section)
				{
					$result[] = [
						'ID' => $section['ID'],
						'VALUE' =>
							($section['DEPTH_LEVEL'] > 1 ? str_repeat('.', $section['DEPTH_LEVEL']) : '')
							. $section['NAME'],
						'GROUP' => $group,
					];
				}
			}

			return $result;
		}
		catch (Main\SystemException $exception)
		{
			return [];
		}
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$selected = Helper\Value::asSingle($arUserField, $arHtmlControl);

		return static::editHtml($arUserField, $arHtmlControl['NAME'], 'radio', $selected);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		$selected = Helper\Value::asMultiple($userField, $htmlControl);

		return static::editHtml($userField, $htmlControl['NAME'], 'checkbox', $selected);
	}

	protected static function editHtml(array $userField, $name, $type, $selected = null)
	{
		try
		{
			static::loadModule();

			$html = '';
			$iblockIds = static::iblockIds($userField);
			$fewIblocks = count($iblockIds) > 1;
			$iblockIndex = 0;

			foreach (static::iblockIds($userField) as $iblockId)
			{
				if ($fewIblocks)
				{
					$html .= sprintf(
						'<div %s><strong>%s</strong></div>',
						$iblockIndex > 0 ? 'style="margin-top: 15px;"' : '',
						\CIBlock::GetArrayByID($iblockId, 'NAME')
					);
				}

				$html .= static::editSectionHtml($iblockId, $name, $type, $selected);

				++$iblockIndex;
			}

			return $html;
		}
		catch (Main\SystemException $exception)
		{
			$message = new \CAdminMessage($exception->getMessage());

			return $message->Show();
		}
	}

	protected static function loadModule()
	{
		if (!Main\Loader::includeModule('iblock'))
		{
			throw new Main\SystemException('cant load iblock module');
		}
	}

	protected static function iblockIds(array $userField)
	{
		$iblockIds = !empty($userField['SETTINGS']['IBLOCK_ID'])
			? (array)$userField['SETTINGS']['IBLOCK_ID']
		    : [];

		Main\Type\Collection::normalizeArrayValuesByInt($iblockIds);

		if (empty($iblockIds))
		{
			throw new Main\SystemException('missing iblock id');
		}

		return $iblockIds;
	}

	protected static function editSectionHtml($iblockId, $name, $type, $selected = null)
	{
		Ui\Assets::loadPlugin('ui.sectionTree', 'css');

		$sections = static::iblockSections($iblockId);
		$tree = [
			[
				'ID' => static::ALL . ':' . $iblockId,
				'NAME' => self::getMessage('ALL'),
				'ITEMS' => static::buildSectionsTree($sections),
			],
		];
		$selectedMap = [];

		if (is_array($selected))
		{
			$selectedMap = array_flip($selected);
		}
		else if (!Utils\Value::isEmpty($selected))
		{
			$selectedMap[$selected] = true;
		}

		if (isset($selectedMap[static::ALL]))
		{
			$selectedMap[static::ALL . ':' . $iblockId] = true;
		}

		$html = sprintf(
			'<div class="b-section-tree %s">',
			static::hasSubtree($tree) ? 'has--subtree' : ''
		);
		$html .= static::renderTree($tree, $name, $type, $selectedMap);
		$html .= '</div>';

		return $html;
	}

	/**
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlWrongAttributeValue
	 */
	protected static function renderTree(array $tree, $name, $type, array $selectedMap)
	{
		$html = '';

		foreach ($tree as $section)
		{
			$sectionSelected = isset($selectedMap[$section['ID']]);
			$control = sprintf(
				'<label><input type="%s" name="%s" value="%s" %s /> %s</label>',
				$type,
				$name,
				$section['ID'],
				$sectionSelected ? 'checked' : '',
				$section['NAME']
			);

			$html .= '<div class="b-section-tree__item">';

			if (!empty($section['ITEMS']))
			{
				$html .= sprintf(<<<EOL
						<details class="b-section-tree" %s>
							<summary class="b-section-tree__summary">%s</summary>
							<div class="b-section-tree__level">%s</div>
						</details>
EOL
					,
					static::hasSelected($section['ITEMS'], $selectedMap) ? 'open' : '',
					$control,
					static::renderTree($section['ITEMS'], $name, $type, $selectedMap)
				);
			}
			else
			{
				$html .= $control;
			}

			$html .= '</div>';
		}

		return $html;
	}

	protected static function hasSelected(array $tree, array $selectedMap)
	{
		if (empty($selectedMap)) { return false; }

		$result = false;

		foreach ($tree as $section)
		{
			if (
				isset($selectedMap[$section['ID']])
				|| (!empty($section['ITEMS']) && static::hasSelected($section['ITEMS'], $selectedMap))
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function hasSubtree(array $tree)
	{
		$result = false;

		foreach ($tree as $section)
		{
			if (!empty($section['ITEMS']))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function iblockSections($iblockId)
	{
		$query = Iblock\SectionTable::getList([
			'select' => [ 'ID', 'NAME', 'DEPTH_LEVEL' ],
			'filter' => [ '=IBLOCK_ID' => $iblockId, '=ACTIVE' => true, '=GLOBAL_ACTIVE' => true ],
			'order' => [ 'LEFT_MARGIN' => 'ASC' ],
		]);

		return $query->fetchAll();
	}

	/** @noinspection OnlyWritesOnParameterInspection */
	protected static function buildSectionsTree(array $sections)
	{
		$tree = [];
		$currentTree = [];

		foreach ($sections as $itemKey => $item)
		{
			$sections[$itemKey]['ITEMS'] = []; // sub level

			if ($item['DEPTH_LEVEL'] > 1)
			{
				$currentTree[ $item['DEPTH_LEVEL'] - 1 ]['ITEMS'][] = &$sections[$itemKey];
			}
			else
			{
				$tree[] = &$sections[$itemKey];
			}

			$currentTree[ $item['DEPTH_LEVEL'] ] = &$sections[$itemKey];
		}

		return $tree;

	}
}