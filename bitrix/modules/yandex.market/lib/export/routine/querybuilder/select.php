<?php
namespace Yandex\Market\Export\Routine\QueryBuilder;

use Yandex\Market\Export;

class Select
{
	public function boot(array $sourceSelect, array &$context)
	{
		$sourceSelect = $this->sortSourceSelect($sourceSelect);
		$sourceSelect = $this->initializeQueryContext($sourceSelect, $context);
		$sourceSelect = $this->sortSourceSelect($sourceSelect);

		return $sourceSelect;
	}

	protected function sortSourceSelect(array $sourceSelect)
	{
		$order = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Export\Entity\Manager::getSource($sourceType);
			$order[$sourceType] = $source->getOrder();
		}

		uksort($sourceSelect, static function($aType, $bType) use ($order) {
			$aOrder = $order[$aType];
			$bOrder = $order[$bType];

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});

		return $sourceSelect;
	}

	protected function initializeQueryContext(array $sourceSelect, array &$iblockContext)
	{
		$originalSelect = $sourceSelect;

		// initial

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Export\Entity\Manager::getSource($sourceType);
			$source->initializeQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}

		// extended select

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$originalFields = isset($originalSelect[$sourceType]) ? (array)$originalSelect[$sourceType] : [];
			$diffFields = array_diff($sourceFields, $originalFields);

			if (empty($diffFields)) { continue; }

			$source = Export\Entity\Manager::getSource($sourceType);

			$source->initializeQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}

		return $sourceSelect;
	}

	public function compile(array $sourceSelect, array $context)
	{
		$result = [
			'ELEMENT' => $this->selectDefaults(),
			'OFFERS' => $this->selectDefaults()
		];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Export\Entity\Manager::getSource($sourceType);
			$querySelect = $source->getQuerySelect($sourceFields);

			foreach ($querySelect as $chainType => $fields)
			{
				if (empty($fields)) { continue; }

				if (!isset($result[$chainType]))
				{
					$result[$chainType] = [];
				}

				foreach ($fields as $field)
				{
					/** @noinspection TypeUnsafeArraySearchInspection */
					if (!in_array($field, $result[$chainType]))
					{
						$result[$chainType][] = $field;
					}
				}
			}
		}

		if (empty($result['CATALOG']))
		{
			// nothing
		}
		else if (!empty($context['OFFER_ONLY']))
		{
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}
		else
		{
			$result['ELEMENT'] = array_merge($result['ELEMENT'], $result['CATALOG']);
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}

		return $result;
	}

	protected function selectDefaults()
	{
		return [ 'IBLOCK_ID',  'ID' ];
	}

	public function release(array $sourceSelect, array $iblockContext)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Export\Entity\Manager::getSource($sourceType);
			$source->releaseQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}
}