<?php

namespace Yandex\Market\Export\Xml\Format\Marketplace;

use Yandex\Market\Data;
use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

class Price extends Catalog
{
	public function getDocumentationLink()
	{
		return null;
	}

	public function useOfferHashCollision()
	{
		return false;
	}

	public function getContext()
	{
		return [
			'CONVERT_CURRENCY' => Data\Currency::getCurrency('RUB'),
		];
	}

	protected function sanitizeRoot(Xml\Tag\Base $root)
	{
		$shop = $root->getChild('shop');

		if ($shop === null) { return; }

		$this->removeChildTags($shop, [ 'cpa', 'enable_auto_discounts', 'categories', 'gifts', 'promos', 'collections' ]);
	}

	public function getCurrency()
	{
		return Xml\Format\YandexMarket\Simple::getCurrency();
	}

	public function getCategory()
	{
		return null;
	}

	public function getCollection()
	{
		return null;
	}

	public function getCollectionId()
	{
		return null;
	}

	public function getOffer()
	{
		return new Xml\Tag\Offer([
			'name' => 'offer',
			'required' => true,
			'visible' => true,
			'attributes' => [
				new Xml\Attribute\Id(['required' => true]),
			],
			'children' => [
				new Xml\Tag\ShopSku(),
				new Xml\Tag\Base(['name' => 'market-sku', 'visible' => true]),
				new Xml\Tag\Price(['required' => true]),
				new Xml\Tag\OldPrice(['visible' => true]),
				new Xml\Tag\Vat(['visible' => true]),
				new Xml\Tag\Weight(['visible' => true]),
				new Xml\Tag\Dimensions(['visible' => true]),
				new Xml\Tag\Disabled(['visible' => true]),
				new Xml\Tag\Count(['visible' => true]),
			]
		]);
	}
}