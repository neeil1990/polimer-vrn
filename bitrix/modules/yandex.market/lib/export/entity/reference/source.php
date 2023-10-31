<?php

namespace Yandex\Market\Export\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Source
{
	protected $type;

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	/**
	 * ���� ��������
	 *
	 * @param array $context
	 *
	 * @return array
	 */
	abstract public function getFields(array $context = []);

    /**
     * ���� ��������
     *
     * @param $fieldId
     * @param array $context
     *
     * @return array|null
     */
    public function getField($fieldId, array $context = [])
    {
        $fields = $this->getFields($context);
        $result = null;

        if (!empty($fields))
        {
            foreach ($fields as $field)
            {
                if ($field['ID'] === $fieldId)
                {
                    $result = $field;
                    break;
                }
            }
        }

        return $result;
    }

	/**
	 * ����� ���� �� ��������
	 *
	 * @param string $query
	 * @param array $context
	 *
	 * @return array
	 */
	public function suggestFields($query, array $context = [])
	{
		$result = [];
		$fields = $this->getFields($context);

		if (!is_array($fields)) { return $result; }

		foreach ($fields as $field)
		{
			if (
				Market\Data\TextString::getPositionCaseInsensitive($field['VALUE'], $query) !== false
				|| Market\Data\TextString::getPositionCaseInsensitive($field['ID'], $query) !== false
			)
			{
				$result[] = $field;
			}
		}

		return $result;
	}

	/**
	 * �������� �������� ��������
	 *
	 * @param       $field
	 * @param array $context
	 *
	 * @return array|null
	 */
	public function getFieldEnum($field, array $context = [])
	{
		$result = null;

		if (!empty($field['TYPE']))
		{
			switch ($field['TYPE'])
			{
				case Market\Export\Entity\Data::TYPE_BOOLEAN:

					$result = [
						[
							'ID' => 'Y',
							'VALUE' => Market\Config::getLang('EXPORT_ENTITY_SOURCE_BOOLEAN_TYPE_ENUM_Y')
						],
						[
							'ID' => 'N',
							'VALUE' => Market\Config::getLang('EXPORT_ENTITY_SOURCE_BOOLEAN_TYPE_ENUM_N')
						]
					];

				break;

				case Market\Export\Entity\Data::TYPE_FILE:

					$result = [
						[
							'ID' => Market\Export\Entity\Data::SPECIAL_VALUE_EMPTY,
							'VALUE' => Market\Config::getLang('EXPORT_ENTITY_SOURCE_FILE_TYPE_ENUM_EMPTY')
						]
					];

				break;

				case Market\Export\Entity\Data::TYPE_DATE:
				case Market\Export\Entity\Data::TYPE_DATETIME:

					$result = [];
					$variants = [
						'HOUR' => 'PT1H',
						'DAY' => 'P1D',
						'WEEK' => 'P1W',
						'MONTH' => 'P1M',
						'QUARTER' => 'P3M',
						'YEAR' => 'P1Y',
					];

					foreach ($variants as $type => $interval)
					{
						$intervalTitle = Market\Config::getLang('EXPORT_ENTITY_SOURCE_DATE_TYPE_ENUM_PERIOD_' . Market\Data\TextString::toUpper($type), null, $type);

						$result[] = [
							'ID' => '-' . $interval,
							'VALUE' => Market\Config::getLang('EXPORT_ENTITY_SOURCE_DATE_TYPE_ENUM_BEFORE_PERIOD', [ '#PERIOD#' => $intervalTitle ]),
						];

						$result[] = [
							'ID' => $interval,
							'VALUE' => Market\Config::getLang('EXPORT_ENTITY_SOURCE_DATE_TYPE_ENUM_AFTER_PERIOD', [ '#PERIOD#' => $intervalTitle ]),
						];
					}

				break;

				case Market\Export\Entity\Data::TYPE_SERVICE_CATEGORY:

					$sectionList = Market\Service\Data\Category::getList();
					$currentTree = [];
					$currentTreeDepth = 0;
					$sectionNameCache = [];
					$result = [];

					foreach ($sectionList as $sectionKey => $section)
					{
						if ($section['depth'] < $currentTreeDepth)
						{
							array_splice($currentTree, $section['depth']);
						}

						$currentTree[$section['depth']] = $sectionKey;
						$currentTreeDepth = $section['depth'];
						$sectionFullName = '';

						foreach ($currentTree as $treeKey)
						{
							$treeSection = $sectionList[$treeKey];
							$treeSectionName = null;

							if (isset($sectionNameCache[$treeSection['id']]))
							{
								$treeSectionName = $sectionNameCache[$treeSection['id']];
							}
							else
							{
								$treeSectionName = Market\Service\Data\Category::getTitle($treeSection['id']);
								$sectionNameCache[$treeSection['id']] = $treeSectionName;
							}

							$sectionFullName .= ($sectionFullName === '' ? '' : ' / ') . $treeSectionName;
						}

						$result[] = [
							'ID' => $section['id'],
							'VALUE' => $sectionFullName
						];
					}

				break;
			}
		}

		return $result;
	}

    /**
     * ������������ ��� ����
     *
     * @param array $field
     * @param string $query
     * @param array $context
     *
     * @return array|null
     */
    public function getFieldAutocomplete($field, $query, array $context = [])
    {
        return null;
    }

    /**
     * ������ �������� ��� ����������� ������������
     *
     * @param array $field
     * @param array $valueList
     * @param array $context
     *
     * @return array|null
     */
    public function getFieldDisplayValue($field, $valueList, array $context = [])
    {
        return null;
    }

	/**
	 * �������� ��������
	 *
	 * @return string
	 */
	public function getTitle()
	{
		$langPrefix = $this->getLangPrefix();

		return Market\Config::getLang($langPrefix . 'TITLE');
	}

	/**
	 * �������� ���������� �������
	 *
	 * @return bool
	 */
	public function isInternal()
	{
		return false;
	}

	/**
	 * ���� ������������: �� ����� ������������ �����, ����� ��������� ����� ��������
	 *
	 * @return bool
	 */
	public function isVariable()
	{
		return $this->getControl() === Market\Export\Entity\Manager::CONTROL_TEXT;
	}

	/**
	 * �������� �� ��������
	 *
	 * @return bool
	 */
	public function isTemplate()
	{
		return $this->getControl() === Market\Export\Entity\Manager::CONTROL_TEMPLATE;
	}

	/**
	 * ��� ���� �����
	 *
	 * @return string
	 */
	public function getControl()
	{
		return Market\Export\Entity\Manager::CONTROL_SELECT;
	}

	/**
	 * ����� ����������� � �������
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return true;
	}

	/**
	 * ���� select ��� ������� CIBlockElement::GetList
	 *
	 * @param $select
	 *
	 * @return array
	 */
	public function getQuerySelect($select)
	{
		return [];
	}

	/**
	 * ����� �� ������������ ������ ��� CIBlockElement::GetList
	 *
	 * @return bool
	 */
	public function isFilterable()
	{
		return false;
	}

	/**
	 * ������ ��� ������� CIBlockElement::GetList
	 *
	 * @param $filter
	 * @param $select
	 *
	 * @return array
	 */
	public function getQueryFilter($filter, $select)
	{
		return [];
	}

	protected function pushQueryFilter(&$filter, $compare, $field, $value)
    {
        $queryKey = $compare . $field;

        if (!isset($filter[$queryKey]))
        {
            $filter[$queryKey] = $value;
        }
        else
        {
            $newValue = (array)$filter[$queryKey];

            if (is_array($value))
            {
                $newValue = array_merge($newValue, $value);
            }
            else
            {
                $newValue[] = $value;
            }

            $filter[$queryKey] = $newValue;
        }
    }

	/**
	 * ������� ���������� ��� ��������� ���������
	 *
	 * @return int
	 * */
	public function getOrder()
	{
		return 500;
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		// nothing by default
	}

	public function releaseQueryContext($select, $queryContext, $sourceSelect)
	{
		// nothing by default
	}

	public function initializeFilterContext($filter, &$queryContext, &$sourceFilter)
	{
		// nothing by default
	}

	public function releaseFilterContext($filter, $queryContext, $sourceFilter)
	{
		// nothing by default
	}

	/**
	 * ������� �������� ����� �� ����������� ������� CIBlockElement::GetList
	 *
	 * @param $elementList
	 * @param $parentList
	 * @param $selectFields
	 * @param $queryContext
	 * @param $sourceValues
	 *
	 * @return array
	 */
	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		return [];
	}

	/**
	 * ��������������� ����� ��� ��������� �������� ����� ��������
	 *
	 * @param $fieldList
	 *
	 * @return array
	 */
	protected function buildFieldsDescription($fieldList)
	{
		$result = [];
		$langPrefix = $this->getLangPrefix();

		foreach ($fieldList as $fieldId => $field)
		{
			$field['ID'] = $fieldId;

			if (!isset($field['VALUE']))
			{
				$field['VALUE'] = Market\Config::getLang($langPrefix . 'FIELD_' . $fieldId, null, $fieldId);
			}

			if (!isset($field['FILTERABLE']))
			{
				$field['FILTERABLE'] = true;
			}

			if (!isset($field['SELECTABLE']))
			{
				$field['SELECTABLE'] = true;
			}

			if (!isset($field['AUTOCOMPLETE']))
			{
				$field['AUTOCOMPLETE'] = false;
			}

			$result[] = $field;
		}

		return $result;
	}

	/**
	 * ������� ��� �������� ���� ������
	 *
	 * @return string
	 */
	abstract protected function getLangPrefix();
}
