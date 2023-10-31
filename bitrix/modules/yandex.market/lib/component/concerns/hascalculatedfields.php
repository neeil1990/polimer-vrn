<?php

namespace Yandex\Market\Component\Concerns;

use Yandex\Market;
use Bitrix\Main;

trait HasCalculatedFields
{
	protected function getCalculatedFields()
	{
		throw new Main\NotImplementedException();
	}

	protected function extractLoadCalculatedParameters(array $queryParameters)
	{
		$calculatedFields = $this->getCalculatedFields();
		$calculatedFieldNames = array_keys($calculatedFields);
		$common = $queryParameters;
		$calculated = [];

		// select

		if (empty($common['select']))
		{
			$calculated['select'] = $calculatedFieldNames;
		}
		else
		{
			$calculated['select'] = array_intersect($common['select'], $calculatedFieldNames);
			$common['select'] = array_diff($common['select'], $calculatedFieldNames);

			foreach ($calculated['select'] as $fieldName)
			{
				$field = $calculatedFields[$fieldName];

				if (empty($field['USES'])) { continue; }

				$common['select'] = array_merge(
					$common['select'],
					$field['USES']
				);
			}

			$common['select'] = array_unique($common['select']);
		}

		// order

		if (!empty($common['order']))
		{
			$common['order'] = array_diff_key($common['order'], $calculatedFields);
		}

		return [ $common, $calculated ];
	}

	protected function loadCalculated($items, $parameters)
	{
		if (empty($parameters['select'])) { return $items; }

		foreach ($items as &$item)
		{
			foreach ($parameters['select'] as $fieldName)
			{
				$item[$fieldName] = $this->loadCalculatedValue($item, $fieldName);
			}
		}
		unset($item);

		return $items;
	}

	protected function loadCalculatedValue($item, $fieldName)
	{
		throw new Main\NotImplementedException();
	}
}
