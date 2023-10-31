<?php

namespace Yandex\Market\Trading\Service\Reference\Document;

use Bitrix\Main;
use Yandex\Market;

interface HasLoadItems
{
	public function loadItems($entitySelect);
}