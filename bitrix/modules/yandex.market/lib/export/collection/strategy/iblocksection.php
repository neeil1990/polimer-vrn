<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Bitrix\Iblock;
use Yandex\Market\Export\Collection;
use Yandex\Market\Export\Entity;
use Yandex\Market\Export\Filter;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui\UserField;
use Yandex\Market\Utils\ArrayHelper;

class IblockSection implements Strategy
{
	use Concerns\HasMessage;
	use Concerns\HasOnce;

	const SUBSECTION_COLLECTION_DISABLE = 'disable';
	const SUBSECTION_COLLECTION_PRODUCT = 'product';
	const SUBSECTION_COLLECTION_ALL = 'all';

	const PREFIX = 'section-';

	protected $values;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFields()
	{
		return [
			'NAME_FIELD' => [
				'TYPE' => 'iblockSectionField',
				'NAME' => self::getMessage('NAME_FIELD'),
				'GROUP' => self::getMessage('FIELD_GROUP'),
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'NAME',
					'STYLE' => 'max-width: 220px',
				],
			],
			'URL_FIELD' => [
				'TYPE' => 'iblockSectionField',
				'NAME' => self::getMessage('URL_FIELD'),
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'SECTION_PAGE_URL',
					'STYLE' => 'max-width: 220px',
				],
			],
			'PICTURE_FIELD' => [
				'TYPE' => 'iblockSectionField',
				'NAME' => self::getMessage('PICTURE_FIELD'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'PICTURE',
					'STYLE' => 'max-width: 220px',
				],
			],
			'DESCRIPTION_FIELD' => [
				'TYPE' => 'iblockSectionField',
				'NAME' => self::getMessage('DESCRIPTION_FIELD'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'DESCRIPTION',
					'STYLE' => 'max-width: 220px',
				],
			],
			'SECTION' => [
				'TYPE' => 'iblockSection',
				'NAME' => self::getMessage('SECTION'),
				'GROUP' => self::getMessage('SECTION_GROUP'),
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => UserField\IblockSectionType::ALL,
				],
			],
			'SUBSECTION_COLLECTION' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('SUBSECTION_COLLECTION'),
				'VALUES' => [
					[ 'ID' => static::SUBSECTION_COLLECTION_PRODUCT, 'VALUE' => self::getMessage('SUBSECTION_COLLECTION_PRODUCT') ],
					[ 'ID' => static::SUBSECTION_COLLECTION_DISABLE, 'VALUE' => self::getMessage('SUBSECTION_COLLECTION_DISABLE') ],
					[ 'ID' => static::SUBSECTION_COLLECTION_ALL, 'VALUE' => self::getMessage('SUBSECTION_COLLECTION_ALL') ],
				],
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
					'DEFAULT_VALUE' => static::SUBSECTION_COLLECTION_PRODUCT,
				],
			],
			'SUBSECTION_PRODUCTS' => [
				'TYPE' => 'boolean',
				'NAME' => self::getMessage('SUBSECTION_PRODUCTS'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => UserField\BooleanType::VALUE_Y,
				],
			],
		];
	}

	public function setValues(array $values)
	{
		$this->values = $values;
	}

	public function getFeedCollections()
	{
		return $this->once('getFeedCollections', null, function() {
			$sections = $this->selectedSections();
			$result = [];

			foreach (ArrayHelper::groupBy($sections, 'IBLOCK_ID') as $iblockId => $iblockSections)
			{
				foreach ($this->sectionRows($iblockId, array_column($iblockSections, 'ID')) as $section)
				{
					$section['PRIMARY'] = static::PREFIX . $section['ID'];

					$productCollection = ProductFacade::emulateProductCollection(
						$this->values['COLLECTION_ID'],
						$iblockId,
						Filter\Facade::compile([
							'SECTION_ID' => $section['ID'],
							'INCLUDE_SUBSECTIONS' => $this->values['SUBSECTION_PRODUCTS'] ? 'Y' : 'N',
						])
					);

					$result[] = new Collection\Data\FeedCollection($section, $productCollection);
				}
			}

			return $result;
		});
	}

	protected function selectedSections()
	{
		$filter = $this->makeSectionsFilter($this->values['SECTION']);

		if ($filter === null) { return []; }

		$select = [ 'ID', 'IBLOCK_ID' ];

		if ($this->values['SUBSECTION_COLLECTION'] !== static::SUBSECTION_COLLECTION_DISABLE)
		{
			$select[] = 'LEFT_MARGIN';
			$select[] = 'RIGHT_MARGIN';
		}

		$query = Iblock\SectionTable::getList([
			'filter' => [
				$filter,
				'=ACTIVE' => true,
				'=GLOBAL_ACTIVE' => true,
			],
			'select' => $select,
		]);

		$sections = ArrayHelper::columnToKey($query->fetchAll(), 'ID');

		if ($this->values['SUBSECTION_COLLECTION'] === static::SUBSECTION_COLLECTION_ALL)
		{
			$sections = $this->addSubsections($sections);
		}
		else if ($this->values['SUBSECTION_COLLECTION'] === static::SUBSECTION_COLLECTION_PRODUCT)
		{
			$sections = $this->addSubsections($sections);
			$sections = $this->filterSectionsWithProducts($sections);
		}

		return $sections;
	}

	protected function makeSectionsFilter($selected)
	{
		if (!is_array($selected)) { return null; }

		$elementIds = [];
		$iblockIds = [];

		foreach ($selected as $id)
		{
			if (is_numeric($id))
			{
				$elementIds[] = $id;
			}
			else if (mb_strpos($id, UserField\IblockSectionType::ALL) === 0)
			{
				$iblockIds[] = (int)mb_substr($id, mb_strlen(UserField\IblockSectionType::ALL) + 1);
			}
		}

		$partials = [];

		if (!empty($elementIds))
		{
			$partials[] = [ '=ID' => $elementIds ];
		}

		if (!empty($iblockIds))
		{
			$partials[] = [ '=IBLOCK_ID' => $iblockIds, '=DEPTH_LEVEL' => 1, ];
		}

		$partials = array_filter($partials);

		if (empty($partials)) { return null; }

		if (count($partials) > 1)
		{
			return [ 'LOGIC' => 'OR' ] + $partials;
		}

		return $partials;
	}

	protected function addSubsections(array $sections)
	{
		$subsectionFilters = [];

		foreach ($sections as $section)
		{
			if ($section['LEFT_MARGIN'] + 1 < $section['RIGHT_MARGIN'])
			{
				$subsectionFilters[] = [
					'=IBLOCK_ID' => $section['IBLOCK_ID'],
					'>LEFT_MARGIN' => $section['LEFT_MARGIN'],
					'<RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
				];
			}
		}

		if (empty($subsectionFilters)) { return $sections; }

		if (count($subsectionFilters) > 1)
		{
			$subsectionFilters = [ 'LOGIC' => 'OR' ] + $subsectionFilters;
		}

		$query = Iblock\SectionTable::getList([
			'filter' => $subsectionFilters,
			'select' => [ 'IBLOCK_ID', 'ID' ],
		]);

		$sections += ArrayHelper::columnToKey($query->fetchAll(), 'ID');

		return $sections;
	}

	protected function filterSectionsWithProducts(array $sections)
	{
		if (empty($sections)) { return []; }

		foreach (ArrayHelper::groupBy($sections, 'IBLOCK_ID') as $iblockId => $iblockSections)
		{
			$defaultFilter = [
				'ACTIVE' => 'Y',
				'ACTIVE_DATE' => 'Y',
			];

			if ($this->canCheckProductAvailable($iblockId))
			{
				$availableKey = Entity\Catalog\Provider::useCatalogShortFields() ? 'AVAILABLE' : 'CATALOG_AVAILABLE';
				$defaultFilter[$availableKey] = 'Y';
			}

			foreach ($sections as $key => $section)
			{
				$filter = [ 'IBLOCK_ID' => $section['IBLOCK_ID'], 'SECTION_ID' => $section['ID'] ];
				$filter += $defaultFilter;

				$query = \CIBlockElement::GetList(
					[],
					$filter,
					false,
					[ 'nTopCount' => 1 ],
					[ 'ID' ]
				);

				if (!$query->Fetch())
				{
					unset($sections[$key]);
				}
			}
		}

		return $sections;
	}

	protected function canCheckProductAvailable($iblockId)
	{
		$context = Entity\Iblock\Provider::getContext($iblockId);

		if (empty($context['HAS_CATALOG'])) { return false; }
		if (empty($context['HAS_OFFER'])) { return true; }

		return !Entity\Catalog\Provider::useCatalogTypeCompatibility();
	}

	protected function sectionRows($iblockId, array $sectionIds)
	{
		if (empty($sectionIds)) { return []; }

		$result = [];

		$fieldsMap = array_filter([
			'URL' => $this->values['URL_FIELD'],
			'NAME' => $this->values['NAME_FIELD'],
			'PICTURE' => $this->values['PICTURE_FIELD'],
			'DESCRIPTION' => $this->values['DESCRIPTION_FIELD'],
		]);

		$query = \CIBlockSection::GetList(
			[ 'LEFT_MARGIN' => 'ASC' ],
			[
				'IBLOCK_ID' => $iblockId,
				'ID' => $sectionIds,
			],
			false,
			array_merge(
				[ 'IBLOCK_ID', 'ID' ],
				array_values($fieldsMap)
			)
		);

		while ($section = $query->GetNext())
		{
			$exportSection = [
				'ID' => $section['ID'],
			];

			foreach ($fieldsMap as $target => $field)
			{
				$exportSection[$target] = isset($section[$field])
					? $this->displayValue($iblockId, $field, $section[$field])
					: null;
			}

			$result[] = $exportSection;
		}

		return $result;
	}

	protected function displayValue($iblockId, $field, $value)
	{
		global $USER_FIELD_MANAGER;

		$display = $value;

		if ($field === 'PICTURE' || $field === 'DETAIL_PICTURE')
		{
			$display = \CFile::GetPath($value);
		}
		else if (!empty($value) && mb_strpos($field, 'UF_') === 0)
		{
			$userFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iblockId . '_SECTION');

			if (isset($userFields[$field]) && $userFields[$field]['USER_TYPE_ID'] === 'file')
			{
				$userField = $userFields[$field];
				$display = [];

				foreach ((array)$value as $fileId)
				{
					$src = \CFile::GetPath($fileId);

					if ($src === null || $src === '') { continue; }

					$display[] = $src;
				}

				if ($userField['MULTIPLE'] !== 'Y')
				{
					$display = reset($display);
				}
			}
		}

		return $display;
	}
}