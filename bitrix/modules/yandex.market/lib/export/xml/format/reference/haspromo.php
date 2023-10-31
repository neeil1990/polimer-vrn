<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

use Yandex\Market\Type;
use Yandex\Market\Export\Xml;
use Yandex\Market\Export\Promo;
use Yandex\Market\Export\PromoProduct;
use Yandex\Market\Export\PromoGift;

trait HasPromo
{
	public function getPromoParentName()
	{
		return 'promos';
	}

	public function getPromo($type = null)
	{
		$result = new Xml\Tag\Base([
			'name' => 'promo',
			'empty_value' => true,
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'required' => true, 'primary' => true]),
				new Xml\Attribute\Base(['name' => 'type', 'required' => true])
			],
			'children' => []
		]);
		$isDateRequired = ($type === Promo\Table::PROMO_TYPE_FLASH_DISCOUNT || $type === Promo\Table::PROMO_TYPE_BONUS_CARD);

		// overview

		$result->addChild(new Xml\Tag\Base(['name' => 'start-date', 'value_type' => Type\Manager::TYPE_DATE, 'required' => $isDateRequired]));
		$result->addChild(new Xml\Tag\Base(['name' => 'end-date', 'value_type' => Type\Manager::TYPE_DATE, 'required' => $isDateRequired]));
		$result->addChild(new Xml\Tag\Base(['name' => 'description', 'value_type' => Type\Manager::TYPE_HTML, 'max_length' => 500]));
		$result->addChild(new Xml\Tag\Base(['name' => 'url', 'value_type' => Type\Manager::TYPE_URL, 'required' => true]));

		// promocode rules

		if ($type === Promo\Table::PROMO_TYPE_PROMO_CODE)
		{
			$result->addChild(new Xml\Tag\Base(['name' => 'promo-code', 'required' => true, 'max_length' => 20]));
			$result->addChild(new Xml\Tag\Base([
				'name' => 'discount',
				'value_type' => Type\Manager::TYPE_NUMBER,
				'value_positive' => true,
				'required' => true,
				'attributes' => [
					new Xml\Attribute\DiscountUnit(['name' => 'unit', 'required' => true]),
					new Xml\Attribute\DiscountCurrency(['name' => 'currency', 'value_type' => Type\Manager::TYPE_CURRENCY, 'required' => true]),
				]
			]));
		}

		// purchase

		$purchase = new Xml\Tag\Base([
			'name' => 'purchase',
			'required' => true,
			'children' => []
		]);

		if ($type === Promo\Table::PROMO_TYPE_GIFT_N_PLUS_M)
		{
			$purchase->addChild(new Xml\Tag\Base(['name' => 'required-quantity', 'value_type' => Type\Manager::TYPE_NUMBER, 'value_positive' => true, 'required' => true]));
			$purchase->addChild(new Xml\Tag\Base(['name' => 'free-quantity', 'value_type' => Type\Manager::TYPE_NUMBER, 'value_positive' => true, 'required' => true]));
		}
		else if ($type === Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
		{
			$purchase->addChild(new Xml\Tag\Base(['name' => 'required-quantity', 'value_type' => Type\Manager::TYPE_NUMBER, 'value_positive' => true]));
		}

		$purchase->addChild(new Xml\Tag\Plain(['name' => 'product', 'multiple' => true, 'required' => true]));

		$result->addChild($purchase);

		// gift

		if ($type === Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
		{
			$result->addChild(new Xml\Tag\Base([
				'name' => 'promo-gifts',
				'required' => true,
				'children' => [
					new Xml\Tag\Plain(['name' => 'promo-gift', 'multiple' => true, 'required' => true])
				]
			]));
		}

		return $result;
	}

	public function getPromoProductParentName()
	{
		return 'purchase';
	}

	public function getPromoProduct($type = null)
	{
		$result = new Xml\Tag\Base([
			'name' => 'product',
			'empty_value' => true
		]);

		if ($type === PromoProduct\Table::PROMO_PRODUCT_TYPE_CATEGORY)
		{
			$result->addAttribute(new Xml\Attribute\Base(['name' => 'category-id', 'required' => true, 'primary' => true]));
		}
		else
		{
			$result->addAttribute(new Xml\Attribute\Base(['name' => 'offer-id', 'required' => true, 'primary' => true]));

			if ($type === PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER_WITH_DISCOUNT)
			{
				$result->addChild(new Xml\Tag\Base([
					'name' => 'discount-price',
					'value_type' => Type\Manager::TYPE_NUMBER,
					'required' => true,
					'attributes' => [
						new Xml\Attribute\Base(['name' => 'currency', 'value_type' => Type\Manager::TYPE_CURRENCY, 'required' => true])
					]
				]));
			}
		}

		return $result;
	}

	public function getPromoGiftParentName()
	{
		return 'promo-gifts';
	}

	public function getPromoGift($type = null)
	{
		$result = new Xml\Tag\Base([
			'name' => 'promo-gift',
			'empty_value' => true,
			'attributes' => [],
		]);

		if ($type === PromoGift\Table::PROMO_GIFT_TYPE_GIFT)
		{
			$result->addAttribute(new Xml\Attribute\Base(['name' => 'gift-id', 'required' => true, 'primary' => true]));
		}
		else
		{
			$result->addAttribute(new Xml\Attribute\Base(['name' => 'offer-id', 'required' => true, 'primary' => true]));
		}

		return $result;
	}

	public function getGiftParentName()
	{
		return 'gifts';
	}

	public function getGift()
	{
		return new Xml\Tag\Base([
			'name' => 'gift',
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'required' => true, 'primary' => true])
			],
			'children' => [
				new Xml\Tag\Base(['name' => 'name', 'required' => true]),
				new Xml\Tag\Base(['name' => 'picture', 'value_type' => Type\Manager::TYPE_FILE, 'required' => true])
			]
		]);
	}
}