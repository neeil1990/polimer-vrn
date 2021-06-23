<?php

namespace Yandex\Market\Export\Xml\Format\Marketplace;

use Yandex\Market\Data;
use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

class Price extends VendorModel
{
	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/marketplace/catalog/yml-elements.html';
	}

	public function getContext()
	{
		return [
			'CONVERT_CURRENCY' => Data\Currency::getCurrency('RUB'),
		];
	}

	public function getRoot()
	{
		$result = parent::getRoot();
		$shop = $result->getChild('shop');

		if ($shop !== null)
		{
			$this->removeChildTags($shop, [ 'cpa', 'categories', 'enable_auto_discounts', 'gifts', 'promos' ]);
		}

		return $result;
	}

	public function isSupportDeliveryOptions()
	{
		return false;
	}

	public function getCategory()
	{
		return null;
	}

	public function getPromo($type = null)
	{
		return null;
	}

	public function getPromoProduct($type = null)
	{
		return null;
	}

	public function getPromoGift($type = null)
	{
		return null;
	}

	public function getGift()
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
				new Xml\Tag\ShopSku(['required' => true]),
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