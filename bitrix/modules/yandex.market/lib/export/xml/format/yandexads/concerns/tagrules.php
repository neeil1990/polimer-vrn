<?php

namespace Yandex\Market\Export\Xml\Format\YandexAds\Concerns;

use Yandex\Market\Export\Xml\Tag;

/**
 * @method removeChildTags(Tag\Base $parent, array $names)
 * @method overrideTags(Tag\Base[] $tags, array $rules)
 */
trait TagRules
{
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

	protected function sanitizeRoot(Tag\Base $root)
	{
		$shop = $root->getChild('shop');

		if ($shop !== null)
		{
			$this->removeChildTags($shop, [
				'cpa',
				'enable_auto_discounts',
			]);
		}

		return $root;
	}

	protected function sanitizeOffer(Tag\Base $offer)
	{
		$this->overrideTags($offer->getChildren(), [
			'picture' => [ 'required' => false ],
			'description' => [ 'required' => true, 'value_tags' => '<h3><br><ul><ol><li><p>' ],
		]);

		$this->removeChildTags($offer, [
			'cpa',
			'enable_auto_discounts',
			'count',
		]);

		return $offer;
	}
}