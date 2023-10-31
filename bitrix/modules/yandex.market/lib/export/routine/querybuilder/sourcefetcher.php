<?php
namespace Yandex\Market\Export\Routine\QueryBuilder;

use Yandex\Market\Export\Entity;

class SourceFetcher
{
	public function load(array $sourceSelect, array $elementList, array $parentList, array $context)
	{
		$result = [];

		foreach (array_chunk($elementList, 500, true) as $elementsPart)
		{
			$parentPart = array_intersect_key($parentList, array_column($elementsPart, 'PARENT_ID', 'PARENT_ID'));
			$valuesChunk = $this->extractElementListValues($sourceSelect, $elementsPart, $parentPart, $context);

			$result += $valuesChunk;
		}

		return $result;
	}

	protected function extractElementListValues(array $sourceSelect, array $elementList, array $parentList, array $context)
	{
		$result = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Entity\Manager::getSource($sourceType);
			$sourceValues = $source->getElementListValues($elementList, $parentList, $sourceFields, $context, $result);

			foreach ($sourceValues as $elementId => $elementValues)
			{
				if (!isset($result[$elementId]))
				{
					$result[$elementId] = [];
				}

				$result[$elementId][$sourceType] = $elementValues;
			}
		}

		return $result;
	}
}