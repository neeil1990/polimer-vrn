<?php

namespace Yandex\Market\Export\Xml\Format\YandexAds;

use Yandex\Market\Export\Xml\Format\Reference\FormatDeprecated;
use Yandex\Market\Export\Xml\Format\YandexMarket;

class Simple extends YandexMarket\Simple
	implements FormatDeprecated
{
	use Concerns\TagRules;

	public function getDocumentationLink()
	{
		return Data\Info::getDocumentationLink();
	}

	public function getPublishNote()
	{
		return Data\Info::getPublishNote();
	}

	public function getSupportedFields()
	{
		return [
			'SHOP_DATA',
		];
	}

	public function getRoot()
	{
		$result = parent::getRoot();

		return $this->sanitizeRoot($result);
	}

	public function getOffer()
	{
		$result = parent::getOffer();

		return $this->sanitizeOffer($result);
	}
}