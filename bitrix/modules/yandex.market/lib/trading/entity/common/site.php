<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class Site extends Market\Trading\Entity\Reference\Site
{
	public function getVariants()
	{
		$variants = Market\Data\Site::getVariants();
		$crmSites = $this->filterCrmSites($variants);

		if (!empty($crmSites))
		{
			$crmMap = array_flip($crmSites);

			uasort($variants, static function($siteA, $siteB) use ($crmMap) {
				$sortA = isset($crmMap[$siteA]) ? 1 : 0;
				$sortB = isset($crmMap[$siteB]) ? 1 : 0;

				if ($sortA === $sortB) { return 0; }

				return ($sortA < $sortB ? -1 : 1);
			});
		}

		return $variants;
	}

	public function getTitle($siteId)
	{
		return Market\Data\Site::getTitle($siteId);
	}

	public function getLanguage($siteId)
	{
		return Market\Data\Site::getLanguage($siteId);
	}

	protected function filterCrmSites($variants)
	{
		$result = [];

		foreach ($variants as $siteId)
		{
			if (!Market\Data\Site::isCrm($siteId)) { continue; }

			$result[] = $siteId;
		}

		return $result;
	}
}