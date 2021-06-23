<?php

namespace Yandex\Market\Component\Base;

abstract class GridList extends AbstractProvider
{
	abstract public function getDefaultSort();

	abstract public function getDefaultFilter();

	abstract public function getFields(array $select = []);

	abstract public function load(array $queryParameters = []);

	abstract public function loadTotalCount(array $queryParameters = []);

	abstract public function deleteItem($id);

	abstract public function filterActions($item, $actions);

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