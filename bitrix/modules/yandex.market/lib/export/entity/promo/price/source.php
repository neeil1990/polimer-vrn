<?php

namespace Yandex\Market\Export\Entity\Promo\Price;

use Yandex\Market;
use Bitrix\Main;

class Source extends Market\Export\Entity\Catalog\Price\Source
{
	public function isInternal()
	{
		return true;
	}

	protected function getContextUserGroups($context)
	{
		if (!empty($context['PROMO_USER_GROUPS']))
		{
			$result = $context['PROMO_USER_GROUPS'];
		}
		else
		{
			$result = parent::getContextUserGroups($context);
		}

		return $result;
	}
}