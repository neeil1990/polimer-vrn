<?php

namespace Yandex\Market\Export\Xml\Format\Marketplace\Concerns;

use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

/**
 * @method removeChildTags(Xml\Tag\Base $tag, string[] $names)
 * @method overrideTags(Xml\Tag\Base[] $tags, array $overrides)
 * @method overrideAttributes(Xml\Attribute\Base[] $attributes, array $overrides)
 */
trait TagRules
{
	public function isSupportDeliveryOptions()
	{
		return false;
	}

	public function getCurrency()
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

	protected function sanitizeRoot(Xml\Tag\Base $root)
	{
		$shop = $root->getChild('shop');

		if ($shop === null) { return; }

		$this->removeChildTags($shop, [ 'cpa', 'enable_auto_discounts', 'currencies', 'gifts', 'promos' ]);
	}

	protected function extendOffer(Xml\Tag\Base $offer)
	{
		$offer->addChild(new Xml\Tag\Base(['name' => 'manufacturer', 'visible' => true]), 'manufacturer_warranty');

		$offer->addChildren([
			new Xml\Tag\ShopSku(),
			new Xml\Tag\Base(['name' => 'market-sku']),
			new Xml\Tag\Base([
				'name' => 'availability',
				'value_type' => Type\Manager::TYPE_BOOLEAN,
				'overrides' => [
					'true' => 'ACTIVE',
					'false' => 'INACTIVE',
					'archive' => 'DELISTED',
				],
			]),
		], 'disabled');

		$offer->addChildren([
			new Xml\Tag\Base(['name' => 'transport-unit', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'min-delivery-pieces', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'quantum', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'leadtime', 'value_type' => Type\Manager::TYPE_NUMBER]),
		], 'box-count');

		$offer->addChildren([
			new Xml\Tag\Base(['name' => 'delivery-weekday', 'wrapper_name' => 'delivery-weekdays', 'multiple' => true, 'value_type' => Type\Manager::TYPE_WEEKDAY]),
		], 'box-count', true);
	}

	protected function sanitizeOffer(Xml\Tag\Base $offer)
	{
		$this->overrideAttributes($offer->getAttributes(), [
			'available' => [ 'visible' => false, 'preselect' => false ]
		]);

		$this->overrideTags($offer->getChildren(), [
			'picture' => [ 'required' => false ],
			'country_of_origin' => [ 'visible' => true ],
			'dimensions' => [ 'visible' => true ],
			'weight' => [ 'visible' => true ],
		]);

		$this->removeChildTags($offer, ['credit-template']);
	}
}
