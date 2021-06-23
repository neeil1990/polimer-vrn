<?php

namespace Yandex\Market\Utils\UserField;

use Bitrix\Main;
use Yandex\Market;

class DefaultValue
{
	protected $entityType;

	public function __construct($entityType)
	{
		$this->entityType = $entityType;
	}

	public function getValues($filter = null)
	{
		$result = [];

		foreach ($this->getFields() as $name => $field)
		{
			if (!$this->applyFilter($field, $filter)) { continue; }

			try
			{
				$value = $this->getValue($field);

				if ($value !== null)
				{
					$result[$name] = $value;
				}
			}
			catch (Main\SystemException $exception)
			{
				// nothing
			}
		}

		return $result;
	}

	protected function getFields()
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER->GetUserFields($this->entityType);
	}

	protected function isFieldMultiple($field)
	{
		return $field['MULTIPLE'] !== 'N';
	}

	protected function applyFilter($field, $filter)
	{
		if ($filter === null)
		{
			$result = true;
		}
		else if (is_string($filter))
		{
			$result = isset($field[$filter]) && $field[$filter] === 'Y';
		}
		else if (is_callable($filter))
		{
			$result = $filter($field);
		}
		else
		{
			throw new Main\NotImplementedException('not implemented filter type');
		}

		return $result;
	}

	protected function getValue($field)
	{
		if (
			isset($field['SETTINGS']['DEFAULT_VALUE'])
			&& !Market\Utils\Value::isEmpty($field['SETTINGS']['DEFAULT_VALUE'])
		)
		{
			$result = $field['SETTINGS']['DEFAULT_VALUE'];
		}
		else
		{
			$result = $this->makeValue($field);
		}

		return $this->sanitizeValue($field, $result);
	}

	protected function makeValue($field)
	{
		$userTypeId = $field['USER_TYPE_ID'];

		switch ($userTypeId)
		{
			case 'enumeration':
				$result = $this->makeEnumerationValue($field);
			break;

			case 'boolean':
				$result = $this->makeBooleanValue($field);
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	protected function makeEnumerationValue($field)
	{
		if (!isset($field['USER_TYPE']['CLASS_NAME']))
		{
			throw new Main\SystemException('user type class name not set');
		}

		$className = $field['USER_TYPE']['CLASS_NAME'];

		if (!method_exists($className, 'getList'))
		{
			throw new Main\SystemException('enumeration user type must implement getList method');
		}

		$isMultiple = $this->isFieldMultiple($field);
		$query = $className::getList($field);
		$defaults = [];
		$first = null;

		if (!($query instanceof \CDBResult) && !($query instanceof Main\DB\Result))
		{
			throw new Main\SystemException('enumeration user type getList result must be db result');
		}

		while ($option = $query->Fetch())
		{
			if (!isset($option['ID'])) { continue; }

			if (isset($option['DEF']) && $option['DEF'] === 'Y')
			{
				$defaults[] = $option['ID'];

				if (!$isMultiple) { break; }
			}
			else if ($first === null)
			{
				$first = $option['ID'];
			}
		}

		return !empty($defaults) ? $defaults : $first;
	}

	protected function makeBooleanValue($field)
	{
		return 0;
	}

	protected function sanitizeValue($field, $value)
	{
		$isFieldMultiple = $this->isFieldMultiple($field);
		$isValueMultiple = is_array($value);

		if ($isFieldMultiple === $isValueMultiple)
		{
			$result = $value;
		}
		else if ($isValueMultiple)
		{
			$result = reset($value);
		}
		else if ($value !== null)
		{
			$result = [ $value ];
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}