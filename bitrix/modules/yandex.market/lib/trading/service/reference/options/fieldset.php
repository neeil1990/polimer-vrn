<?php

namespace Yandex\Market\Trading\Service\Reference\Options;

use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Fieldset extends Skeleton
{
	public function getFieldDescription(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'MULTIPLE' => 'N',
			'FIELDS' => $this->getFields($environment, $siteId),
		];
	}
}