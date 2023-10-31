<?php
namespace Yandex\Market\Component\Base;

use Bitrix\Main;

/** @property \Yandex\Market\Components\AdminGridList $component */
abstract class GridList extends AbstractProvider
{
	public function getDefaultSort()
	{
		return [];
	}

	public function getDefaultFilter()
	{
		return [];
	}

	abstract public function getFields(array $select = []);

	abstract public function load(array $queryParameters = []);

	abstract public function loadTotalCount(array $queryParameters = []);

	public function deleteItem($id)
	{
		throw new Main\NotSupportedException();
	}

	public function filterActions($item, $actions)
	{
		return $actions;
	}

	public function getContextMenu()
	{
		return [];
	}

	public function getGroupActions()
	{
		return [];
	}

	public function getUiGroupActions()
	{
		return $this->getGroupActions();
	}

	public function getGroupActionParams()
	{
		return [];
	}

	public function getUiGroupActionParams()
	{
		return $this->getGroupActionParams();
	}
}