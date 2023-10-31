<?php

namespace Yandex\Market\Export\Entity\Main\Site;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

class Provider
{
	/**
	 * @deprecated Use Market\Data\SiteDomain::getSite
	 *
	 * @param string $domain
	 * @param string $path
	 *
	 * @return string|null
	 */
	public static function getIdByDomain($domain, $path = '')
	{
		return Market\Data\SiteDomain::getSite($domain, $path);
	}
}