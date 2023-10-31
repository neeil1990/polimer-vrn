<?php

namespace Yandex\Market\Component\Setup;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class GridList extends Market\Component\Model\GridList
	implements Market\Component\Concerns\UiServiceInterface
{
	use Market\Component\Concerns\HasGroup;
	use Market\Component\Concerns\HasUiService;
	use Market\Component\Concerns\HasCalculatedFields;

	protected $groupId;
	protected $repository;

	public function prepareComponentParams($params)
	{
		global $APPLICATION;

		$result = parent::prepareComponentParams($params);
		$result['SERVICE'] = trim($params['SERVICE']);

		if ($result['SERVICE'] !== '')
		{
			$result['BASE_URL'] = $APPLICATION->GetCurPageParam(
				http_build_query([ 'service' => $result['SERVICE'] ]),
				[ 'service' ]
			);
		}

		return $result;
	}

	protected function getReferenceFields()
	{
		$result = parent::getReferenceFields();
		$result['IBLOCK'] = [];

		return $result;
	}

	public function getDefaultFilter()
	{
		$result = parent::getDefaultFilter();
		$serviceFilter = $this->getUiServiceFilter();

		if ($serviceFilter !== null)
		{
			$result[] = $serviceFilter;
		}

		return $result;
	}

	public function processAjaxAction($action, $data)
	{
		$result = null;

		switch ($action)
		{
			case 'move_group':
				$this->processMoveGroupForGroups($data);
				$this->processMoveGroupForItems($data);
			break;

			case 'add_group':
				$this->processMoveGroupForGroups($data);
				$this->processAddGroupForItems($data);
			break;

			default:
				parent::processAjaxAction($action, $data);
			break;
		}

		return $result;
	}

	protected function processMoveGroupForGroups($data)
	{
		$targetGroup = $this->getAjaxActionTargetGroup();
		$selectedIds = $this->getActionSelectedGroups($data);

		foreach ($selectedIds as $selectedId)
		{
			$this->moveGroup($selectedId, $targetGroup);
		}
	}

	protected function processMoveGroupForItems($data)
	{
		$targetGroup = $this->getAjaxActionTargetGroup();

		// delete exists links

		$setupFilter = $this->getActionSelectedFilter($data);
		$filter = $this->mixFilterEntity($setupFilter, 'SETUP');

		$this->deleteGroupLinks($filter);

		// add new links

		if ($targetGroup > 0)
		{
			$selectedIds = $this->getActionSelectedIds($data);

			$this->addGroupLinks($selectedIds, $targetGroup);
		}
	}

	protected function processAddGroupForItems($data)
	{
		$targetGroup = $this->getAjaxActionTargetGroup();
		$selectedIds = $this->getActionSelectedIds($data);
		$existsLinks = $this->getExistsGroupLinks($targetGroup);
		$withoutLinks = $this->getSetupWithoutGroupLinks($selectedIds);
		$newIds = array_diff($selectedIds, $existsLinks);

		if ($targetGroup !== 0)
		{
			$this->addGroupLinks($withoutLinks, 0);
		}

		$this->addGroupLinks($newIds, $targetGroup);
	}

	protected function processDeleteAction($data)
	{
		parent::processDeleteAction($data);
		$this->processDeleteGroups($data);
	}

	protected function processDeleteGroups($data)
	{
		$selectedGroups = $this->getActionSelectedGroups($data);
		$childrenGroups = $this->getGroupChildren($selectedGroups, true);
		$allGroups = array_merge($selectedGroups, $childrenGroups);
		$items = $this->getGroupItems($allGroups);
		$siblingItems = $this->getSiblingGroupItems($allGroups, $items);
		$itemsWithoutSiblings = array_diff($items, $siblingItems);

		foreach ($itemsWithoutSiblings as $itemId)
		{
			$this->deleteItem($itemId);
		}

		foreach ($allGroups as $groupId)
		{
			$this->deleteGroup($groupId);
		}
	}

	protected function mixFilterEntity($filter, $entityName)
	{
		$filterBuilder = new \CSQLWhere();
		$result = [];

		foreach ($filter as $key => $value)
		{
			if (!is_numeric($key))
			{
				$operation = $filterBuilder->MakeOperation($key);
				$conditionLength = Market\Data\TextString::getLength($key) - Market\Data\TextString::getLength($operation['FIELD']);
				$condition = Market\Data\TextString::getSubstring($key, 0, $conditionLength);
				$newKey = $condition . $entityName . '.' . $operation['FIELD'];

				$result[$newKey] = $value;
			}
			else if (is_array($value))
			{
				$result[$key] = $this->mixFilterEntity($value, $entityName);
			}
			else
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}

	protected function deleteGroupLinks($filter)
	{
		$dataClass = $this->getGroupLinkDataClass();

		$dataClass::deleteBatch([
			'filter' => $filter,
		]);
	}

	protected function getExistsGroupLinks($groupId)
	{
		$result = [];
		$dataClass = $this->getGroupLinkDataClass();

		$query = $dataClass::getList([
			'filter' => [ '=GROUP_ID' => $groupId ],
			'select' => [ 'SETUP_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = (int)$row['SETUP_ID'];
		}

		return $result;
	}

	protected function getSetupWithoutGroupLinks($setupIds)
	{
		$result = [];

		$query = Market\Export\Setup\Table::getList([
			'filter' => [ '=ID' => $setupIds, 'GROUP_LINK.GROUP_ID' => false ],
			'select' => [ 'ID', 'GROUP_ID' => 'GROUP_LINK.GROUP_ID' ],
		]);

		while ($row = $query->fetch())
		{
			if ((string)$row['GROUP_ID'] !== '') { continue; } // ignore '0'

			$result[] = (int)$row['ID'];
		}

		return $result;
	}

	protected function getGroupItems($groupIds)
	{
		$groupLinkDataClass = $this->getGroupLinkDataClass();
		$result = [];

		if (empty($groupIds)) { return $result; }

		$query = $groupLinkDataClass::getList([
			'filter' => [ '=GROUP_ID' => $groupIds ],
			'select' => [ 'SETUP_ID' ]
		]);

		while ($row = $query->fetch())
		{
			$result[] = (int)$row['SETUP_ID'];
		}

		return array_unique($result);
	}

	protected function getSiblingGroupItems($groupIds, $itemIds)
	{
		$groupLinkDataClass = $this->getGroupLinkDataClass();
		$result = [];

		if (empty($groupIds) || empty($itemIds)) { return $result; }

		$query = $groupLinkDataClass::getList([
			'filter' => [ '!=GROUP_ID' => $groupIds, '=SETUP_ID' => $itemIds ],
			'select' => [ 'SETUP_ID' ]
		]);

		while ($row = $query->fetch())
		{
			$result[] = (int)$row['SETUP_ID'];
		}

		return array_unique($result);
	}

	protected function getGroupChildren($groupIds, $recursive = false, array $foundGroups = [])
	{
		$dataClass = $this->getGroupDataClass();
		$result = [];

		if (empty($groupIds)) { return $result; }

		$query = $dataClass::getList([
			'filter' => [ '=PARENT_ID' => $groupIds ],
			'select' => [ 'ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = (int)$row['ID'];
		}

		if ($recursive && !empty($result))
		{
			$foundGroups = array_merge($foundGroups, $groupIds);
			$notCheckedGroups = array_diff($result, $foundGroups);

			if (!empty($notCheckedGroups))
			{
				$recursiveChildren = $this->getGroupChildren($result, true, $foundGroups);
				$result = array_merge($result, $recursiveChildren);
			}
		}

		return $result;
	}

	protected function moveGroup($groupId, $parentId)
	{
		$dataClass = $this->getGroupDataClass();
		$updateResult = $dataClass::update($groupId, [ 'PARENT_ID' => $parentId ]);

		Market\Result\Facade::handleException($updateResult);
	}

	protected function deleteGroup($groupId)
	{
		$dataClass = $this->getGroupDataClass();
		$deleteResult = $dataClass::delete($groupId);

		Market\Result\Facade::handleException($deleteResult);
	}

	protected function addGroupLinks($setupIds, $groupId)
	{
		$dataClass = $this->getGroupLinkDataClass();

		foreach ($setupIds as $setupId)
		{
			$dataClass::add([
				'SETUP_ID' => $setupId,
				'GROUP_ID' => $groupId,
			]);
		}
	}

	protected function getAjaxActionTargetGroup()
	{
		return isset($_REQUEST['group_to_move']) ? (int)$_REQUEST['group_to_move'] : null;
	}

	protected function filterGroupPrimaries($ids, $revert = false)
	{
		$result = [];

		foreach ($ids as $id)
		{
			$isGroup = (Market\Data\TextString::getPosition($id, 'G') === 0);

			if ($isGroup === $revert)
			{
				if ($isGroup)
				{
					$id = Market\Data\TextString::getSubstring($id, 1);
				}

				$result[] = (int)$id;
			}
		}

		return $result;
	}

	protected function getActionSelectedIds($data)
	{
		$result = parent::getActionSelectedIds($data);

		return $this->filterGroupPrimaries($result);
	}

	protected function getActionSelectedGroups($data)
	{
		$result = parent::getActionSelectedIds($data);

		return $this->filterGroupPrimaries($result, true);
	}

	public function getFields(array $select = [])
	{
		$result = parent::getFields($select);
		$result = $this->allowGroupFields($result);

		if (isset($result['GROUP']))
		{
			$result['GROUP'] = $this->modifyGroupField($result['GROUP']);
		}

		if (isset($result['EXPORT_FORMAT'], $result['EXPORT_SERVICE']))
		{
			$result['EXPORT_SERVICE'] = $this->getRepository()->modifyExportServiceField($result['EXPORT_SERVICE']);
			$result['EXPORT_FORMAT'] = $this->getRepository()->modifyExportFormatField($result['EXPORT_FORMAT'], $result['EXPORT_SERVICE']);

			$this->resolveExportServiceFilter($result['EXPORT_SERVICE']);
		}

		$result += $this->getCalculatedFields();

		return $result;
	}

	protected function getCalculatedFields()
	{
		return [
			'EXPORT_DATE' => [
				'USER_TYPE' => Market\Ui\UserField\Manager::getUserType('datetime'),
				'FIELD_NAME' => 'EXPORT_DATE',
				'LIST_COLUMN_LABEL' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_FIELD_EXPORT_DATE'),
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'USES' => [
					'ID',
					'FILE_NAME',
				]
			],
		];
	}

	protected function modifyGroupField($field)
	{
		if (!isset($field['SETTINGS'])) { $field['SETTINGS'] = []; }

		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('enumeration');
		$field['SETTINGS']['ALLOW_NO_VALUE'] = 'N';
		$field['VALUES'] = $this->getGroupTreeEnum();
		$field['SELECTABLE'] = false;

		return $field;
	}

	protected function resolveExportServiceFilter($field)
	{
		if (!isset($field['VALUES']) || count($field['VALUES']) < 2)
		{
			$filterFields = $this->getComponentParam('FILTER_FIELDS');
			$filterIndex = array_search($field['FIELD_NAME'], $filterFields, true);

			if ($filterIndex !== false)
			{
				array_splice($filterFields, $filterIndex, 1);

				$this->setComponentParam('FILTER_FIELDS', $filterFields);
			}
		}
	}

	/**
	 * @return Main\Entity\DataManager
	 */
	protected function getGroupDataClass()
	{
		return Market\Export\Setup\Internals\GroupTable::class;
	}

	/**
	 * @return Main\Entity\DataManager
	 */
	protected function getGroupLinkDataClass()
	{
		return Market\Export\Setup\Internals\GroupLinkTable::class;
	}

	public function load(array $queryParameters = [])
	{
		list($commonParameters, $calculatedParameters) = $this->extractLoadCalculatedParameters($queryParameters);
		$groupId = $this->findLoadParametersGroup($commonParameters);

		$result = parent::load($commonParameters);
		$result = $this->loadCalculated($result, $calculatedParameters);

		$this->setGroupId($groupId);

		if (!isset($queryParameters['offset']) || (int)$queryParameters['offset'] <= 0)
		{
			$groupParameters = $this->makeLoadGroupParameters($groupId);

			$result = array_merge(
				$this->loadGroups($groupParameters),
				$result
			);
		}

		return $result;
	}

	protected function loadCalculatedValue($item, $fieldName)
	{
		switch ($fieldName)
		{
			case 'EXPORT_DATE':
				$result = $this->loadCalculatedExportDate($item);
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	protected function loadCalculatedExportDate($item)
	{
		$setup = new Market\Export\Setup\Model($item);
		$date = Market\Export\Run\Data\ExportDate::getLastUpdate($setup);

		return $date !== null ? $date->toString() : null;
	}

	protected function findLoadParametersGroup(array $queryParameters)
	{
		$result = null;
		$variants = [
			'GROUP',
			'GROUP.ID',
		];

		foreach ($variants as $variant)
		{
			if (isset($queryParameters['filter'][$variant]))
			{
				$result = $queryParameters['filter'][$variant];
				break;
			}
		}

		return $result;
	}

	protected function makeLoadGroupParameters($groupId)
	{
		$result = [];

		if ($groupId !== null)
		{
			$result['filter'] = [ '=PARENT_ID' => $groupId ];
		}

		return $result;
	}

	protected function loadGroups(array $queryParameters = [])
	{
		$dataClass = $this->getGroupDataClass();
		$result = [];

		if (!isset($queryParameters['filter'])) { $queryParameters['filter'] = []; }

		$queryParameters['filter'][] = $this->getUiServiceFilter('UI_SERVICE');

		$query = $dataClass::getList($queryParameters);

		while ($group = $query->fetch())
		{
			$result[] = [
				'PRIMARY' => $group['ID'],
				'ID' => 'G' . $group['ID'],
				'NAME' => $group['NAME'],
				'ROW_TYPE' => 'GROUP',
				'ROW_ICON' => 'iblock-section-icon',
			];
		}

		return $result;
	}

	public function getContextMenu()
	{
		return array_filter([
			$this->getContextMenuAdd(),
			$this->getContextMenuGroupEdit(),
			$this->getContextMenuGroupUp(),
		]);
	}

	protected function getContextMenuAdd()
	{
		$addUrl = (string)$this->getComponentParam('ADD_URL');
		$groupId = $this->getGroupId();

		if ($addUrl === '') { return null; }

		if ($groupId > 0)
		{
			$addUrl .=
				(Market\Data\TextString::getPosition($addUrl, '?') === false ? '?' : '&')
				. 'parent=' . (int)$groupId;
		}

		return [
			'TEXT' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_CONTEXT_ADD'),
			'LINK' => $addUrl,
			'ICON' => 'btn_new'
		];
	}

	protected function getContextMenuGroupEdit()
	{
		$groupEditUrl = (string)$this->getComponentParam('GROUP_EDIT_URL');
		$groupId = $this->getGroupId();

		if ($groupEditUrl === '') { return null; }

		if ($groupId > 0)
		{
			$groupEditUrl .=
				(Market\Data\TextString::getPosition($groupEditUrl, '?') === false ? '?' : '&')
				. 'parent=' . (int)$groupId;
		}

		return [
			'TEXT' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_CONTEXT_GROUP_ADD'),
			'LINK' => $groupEditUrl,
		];
	}

	protected function getContextMenuGroupUp()
	{
		global $APPLICATION;

		$groupId = $this->getGroupId();
		$parentId = $groupId > 0 ? $this->getGroupParentId($groupId) : null;

		if ($parentId === null) { return null; }

		return [
			'TEXT' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_CONTEXT_GROUP_UP'),
			'LINK' => $APPLICATION->GetCurPageParam('find_group=' . $parentId . '&set_filter=Y&apply_filter=Y', [
				'find_group',
				'table_id',
				'mode',
			]),
		];
	}

	public function getGroupActions()
	{
		return [
			'move_group' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_ACTION_GROUP_MOVE'),
			'add_group' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_ACTION_GROUP_ADD'),
			'group_chooser' => $this->getGroupActionGroupChooser(),
		];
	}

	protected function getGroupActionGroupChooser()
	{
		$fields = $this->getComponentResult('FIELDS');
		$variants = isset($fields['GROUP']['VALUES']) ? (array)$fields['GROUP']['VALUES'] : [];

		$groups = '<div id="group_to_move" style="display: none;">';
		$groups .= '<select name="group_to_move">';

		foreach ($variants as $variant)
		{
			$groups .= sprintf(
				'<option value="%s">%s</option>',
				$variant['ID'],
				$variant['VALUE']
			);
		}

		$groups .= '</select>';
		$groups .= '</div>';

		return [
			'type' => 'html',
			'value' => $groups,
		];
	}

	public function getGroupActionParams()
	{
		return [
			'select_onchange' => "BX('group_to_move').style.display = (this.value == 'move_group' || this.value == 'add_group'? 'block':'none');",
		];
	}

	public function getUiGroupActions()
	{
		return [
			'move_group' => [
				'label' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_ACTION_GROUP_MOVE'),
				'type' => 'select',
				'name' => 'group_to_move',
				'items' => $this->getGroupActionGroupVariants(),
			],
			'add_group' => [
				'label' => Market\Config::getLang('COMPONENT_SETUP_GRID_LIST_ACTION_GROUP_ADD'),
				'type' => 'select',
				'name' => 'group_to_move',
				'items' => $this->getGroupActionGroupVariants(),
			],
		];
	}

	protected function getGroupActionGroupVariants()
	{
		$fields = $this->getComponentResult('FIELDS');
		$variants = isset($fields['GROUP']['VALUES']) ? (array)$fields['GROUP']['VALUES'] : [];
		$result = [];

		foreach ($variants as $variant)
		{
			$result[] = [
				'VALUE' => $variant['ID'],
				'NAME' => $variant['VALUE'],
			];
		}

		return $result;
	}

	public function getUiGroupActionParams()
	{
		return [];
	}

	protected function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}

	protected function getGroupId()
	{
		return $this->groupId;
	}

	protected function getGroupParentId($id)
	{
		$result = null;
		$dataClass = $this->getGroupDataClass();

		$query = $dataClass::getList([
			'filter' => [ '=ID' => $id ],
			'select' => [ 'PARENT_ID' ],
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['PARENT_ID'];
		}

		return $result;
	}

	protected function getRepository()
	{
		if ($this->repository === null)
		{
			$this->repository = $this->makeRepository();
		}

		return $this->repository;
	}

	protected function makeRepository()
	{
		$uiService = $this->getUiService();
		$modelClass = $this->getModelClass();

		return new Repository($uiService, $modelClass);
	}
}