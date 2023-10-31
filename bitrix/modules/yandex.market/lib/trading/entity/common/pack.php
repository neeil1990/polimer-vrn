<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;

class Pack extends Market\Trading\Entity\Reference\Pack
{
	protected $formatTag;

	public function getRatio($productIds, array $context = [])
	{
		if (empty($context['SOURCES'])) { return []; }

		$exportContext = $this->buildExportContext($context);
		$sourceSelect = $this->buildExportSelect($context);

		$exportValues = Market\Export\Entity\Facade::loadValues($productIds, $sourceSelect, $exportContext);

		return $this->collectRatioValues($exportValues, $context);
	}

	protected function buildExportSelect(array $context)
	{
		$result = [];

		foreach ($context['SOURCES'] as list($source, $field))
		{
			if (!isset($result[$source]))
			{
				$result[$source] = [];
			}

			$result[$source][] = $field;
		}

		return $result;
	}

	protected function buildExportContext(array $context)
	{
		return array_intersect_key($context, [
			'SITE_ID' => true,
		]);
	}

	protected function collectRatioValues(array $allValues, array $context)
	{
		$result = [];

		foreach ($allValues as $productId => $oneValues)
		{
			foreach ($context['SOURCES'] as list($source, $field))
			{
				if (!isset($oneValues[$source][$field])) { continue; }

				$value = $this->sanitizeRatioValue($oneValues[$source][$field]);

				if ($value === null || $value <= 0) { continue; }

				$result[$productId] = $value;
			}
		}

		return $result;
	}

	protected function sanitizeRatioValue($value)
	{
		if (is_array($value))
		{
			$value = reset($value);
		}

		if (empty($value)) { return null; }

		$type = Market\Type\Manager::getType(Market\Type\Manager::TYPE_NUMBER);
		$node = $this->getFormatTag();

		if (!$type->validate($value, [], $node)) { return null; }

		return (float)$type->format($value, [], $node);
	}

	protected function getFormatTag()
	{
		if ($this->formatTag === null)
		{
			$this->formatTag = new Market\Export\Xml\Tag\Base([
				'name' => 'dummy',
				'value_precision' => 4,
			]);
		}

		return $this->formatTag;
	}
}