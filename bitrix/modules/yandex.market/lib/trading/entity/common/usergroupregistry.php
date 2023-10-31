<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class UserGroupRegistry extends Market\Trading\Entity\Reference\UserGroupRegistry
{
	protected function createGroup($serviceCode, $siteId)
	{
		return new UserGroup($this->environment, $serviceCode, $siteId);
	}
}