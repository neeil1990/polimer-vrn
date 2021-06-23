<?php

namespace Yandex\Market\Export\Xml\Format\Turbo;

use Bitrix\Main;
use Yandex\Market\Export\Xml;

class Simple extends Xml\Format\YandexMarket\Simple
{
	public function getRoot()
	{
		$result = parent::getRoot();
		$shop = $result->getChild('shop');

		if ($shop !== null)
		{
			$this->removeChildTags($shop, [
				'cpa',
				'enable_auto_discounts',
			]);
		}

		return $result;
	}

	public function getOffer()
	{
		$result = parent::getOffer();

		$result->addChild(new Xml\Tag\Vat(), 4);

		$this->overrideTags($result->getChildren(), [
			'description' => [ 'required' => true ],
		]);

		$this->removeChildTags($result, [
			'cpa',
			'enable_auto_discounts',
			'count',
		]);

		return $result;
	}
}