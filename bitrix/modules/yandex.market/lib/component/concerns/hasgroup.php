<?php

namespace Yandex\Market\Component\Concerns;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

trait HasGroup
{
	protected $groupTree;

	protected function allowGroupFields($fields)
	{
		$allowed = [
			'NAME' => true,
		];

		foreach ($fields as $fieldName => &$field)
		{
			if (!isset($allowed[$fieldName])) { continue; }

			$field['ROW_TYPE'] = isset($field['ROW_TYPE']) ? (array)$field['ROW_TYPE'] : [ 'DEFAULT' ];
			$field['ROW_TYPE'][] = 'GROUP';
		}
		unset($field);

		return $fields;
	}

	protected function getGroupTreeEnum()
	{
		$result = [];

		foreach ($this->getGroupTree() as $group)
		{
			$depthDots = $group['DEPTH_LEVEL'] > 0 ? str_repeat('.', $group['DEPTH_LEVEL']) : '';

			$result[] = [
				'ID' => $group['ID'],
				'VALUE' => $depthDots . $group['NAME'],
			];
		}

		return $result;
	}

	protected function getGroupTree()
	{
		if ($this->groupTree === null)
		{
			$this->groupTree = array_merge(
				$this->loadGroupTopLevel(),
				$this->loadGroupStoredTree()
			);
		}

		return $this->groupTree;
	}

	protected function loadGroupTopLevel()
	{
		return [
			[
				'ID' => 0,
				'NAME' => Market\Config::getLang('COMPONENT_CONCERNS_HAS_GROUP_TOP_LEVEL'),
				'DEPTH_LEVEL' => 0,
			],
		];
	}

	protected function loadGroupStoredTree()
	{
		$dataClass = $this->getGroupDataClass();
		$filter = $this->getGroupDefaultFilter();

		return $dataClass::getTree([
			'filter' => $filter,
		]);
	}

	/**
	 * @return array
	 */
	protected function getGroupDefaultFilter()
	{
		$result = [];

		if ($this instanceof UiServiceInterface)
		{
			$result['=UI_SERVICE'] = $this->getUiService()->getCode();
		}

		return $result;
	}

	/**
	 * @return Main\Entity\DataManager
	 */
	protected function getGroupDataClass()
	{
		throw new Main\NotImplementedException('not implemented HasGroup::getGroupDataClass');
	}
}