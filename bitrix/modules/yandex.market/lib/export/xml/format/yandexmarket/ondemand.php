<?php

namespace Yandex\Market\Export\Xml\Format\YandexMarket;

use Yandex\Market\Export\Xml;

class OnDemand extends Simple
{
	public function getType()
	{
		return 'on.demand';
	}

	public function getOffer()
	{
		$result = parent::getOffer();
		$result->addAttribute(new Xml\Attribute\Type(['required' => true]), 1);

		return $result;
	}
}
