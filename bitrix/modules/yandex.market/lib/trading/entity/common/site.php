<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class Site extends Market\Trading\Entity\Reference\Site
{
	public function getVariants()
	{
		return Market\Data\Site::getVariants();
	}

	public function getTitle($siteId)
	{
		return Market\Data\Site::getTitle($siteId);
	}
}