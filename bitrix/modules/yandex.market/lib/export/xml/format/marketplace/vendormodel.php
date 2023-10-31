<?php

namespace Yandex\Market\Export\Xml\Format\Marketplace;

use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

/** @deprecated */
class VendorModel extends Xml\Format\YandexMarket\VendorModel
{
	use Concerns\TagRules;

	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/marketplace/catalog/yml-simple.html';
	}

	public function getRoot()
	{
		$result = parent::getRoot();

		$this->sanitizeRoot($result);

		return $result;
	}

	public function getOffer()
	{
		$result = parent::getOffer();

		$this->extendOffer($result);
		$this->sanitizeOffer($result);
		$this->overrideTags($result->getChildren(), [
			'model' => [ 'name' => 'name' ],
		]);

		return $result;
	}
}