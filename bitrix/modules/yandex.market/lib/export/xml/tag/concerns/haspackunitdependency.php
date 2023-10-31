<?php

namespace Yandex\Market\Export\Xml\Tag\Concerns;

trait HasPackUnitDependency
{
	protected function copyPricePackUnitSetting(&$tagDescriptionList, array $context = [])
	{
		$packRatio = null;
		$parentDescriptionList = !empty($context['TAG_CHAIN']) ? reset($context['TAG_CHAIN']) : $tagDescriptionList;

		// find price ratio

		foreach ($parentDescriptionList as $tagDescription)
		{
			if ($tagDescription['TAG'] !== 'price') { continue; }

			if (isset($tagDescription['SETTINGS']['PACK_RATIO']))
			{
				$packRatio = $tagDescription['SETTINGS']['PACK_RATIO'];
			}

			break;
		}

		// write to self settings

		foreach ($tagDescriptionList as &$tagDescription)
		{
			if ($tagDescription['TAG'] !== $this->id) { continue; }

			$tagDescription['SETTINGS']['PACK_RATIO'] = $packRatio;
		}
		unset($tagDescription);
	}
}