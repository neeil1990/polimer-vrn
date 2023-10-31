<?php

namespace Yandex\Market\Export\Xml\Format\YandexMarket;

use Yandex\Market\Export\Xml;

class Simple extends VendorModel
{
	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/marketplace/assortment/fields/index.html';
	}

	public function getType()
	{
		return 'simple';
	}

	/**
	 * @return Xml\Tag\Base
	 */
	public function getOffer()
	{
		return new Xml\Tag\Offer([
			'name' => 'offer',
			'required' => true,
			'attributes' => [
				new Xml\Attribute\Id(['required' => true]),
				new Xml\Attribute\Available(['visible' => true, 'preselect' => true]),
				new Xml\Attribute\Base(['name' => 'bid', 'value_type' => 'number']),
				new Xml\Attribute\GroupId(['preselect' => true]),
			],
			'children' => array_merge(
				$this->getOfferDefaultChildren('prolog'),
				[
					new Xml\Tag\Name(['required' => true]),
					new Xml\Tag\Model(),
					new Xml\Tag\Vendor(),
					new Xml\Tag\Base(['name' => 'vendorCode'])
				],
				$this->getOfferDefaultChildren('epilog', null, [
					'barcode' => 95 // after param
				])
			)
		]);
	}
}
