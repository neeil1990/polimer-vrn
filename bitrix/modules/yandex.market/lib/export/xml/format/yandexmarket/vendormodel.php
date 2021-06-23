<?php

namespace Yandex\Market\Export\Xml\Format\YandexMarket;

use Bitrix\Main;
use Yandex\Market\Export\PromoGift;
use Yandex\Market\Export\Xml;
use Yandex\Market\Type;
use Yandex\Market\Export\Promo;
use Yandex\Market\Export\PromoProduct;
use Yandex\Market\Utils;

class VendorModel extends Xml\Format\Reference\Base
{
	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/partnermarket/export/vendor-model.html';
	}

	public function getType()
	{
		return 'vendor.model';
	}

	public function getContext()
	{
		return [];
	}

	public function isSupportDeliveryOptions()
	{
		return true;
	}

	public function getHeader()
	{
		$encoding = Utils\Encoding::getCharset();

		$result = '<?xml version="1.0" encoding="' . $encoding . '"?>';
		$result .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">';

		return $result;
	}

	public function getRoot()
	{
		$result = new Xml\Tag\Base([
			'name' => 'yml_catalog',
			'attributes' => [
				new Xml\Attribute\Base([
					'name' => 'date',
					'value_type' => Type\Manager::TYPE_DATE,
					'default_value' => time(),
					'date_format' => \DateTime::ATOM,
				]),
			],
			'children' => [
				new Xml\Tag\Base([
					'name' => 'shop',
					'children' => [
						new Xml\Tag\ShopName(),
						new Xml\Tag\ShopCompany(),
						new Xml\Tag\ShopUrl(),
						new Xml\Tag\ShopPlatform(),
						new Xml\Tag\ShopPlatformVersion(),
						new Xml\Tag\Cpa([ 'global' => true ]),
						new Xml\Tag\Root([ 'name' => 'currencies' ]),
						new Xml\Tag\Root([ 'name' => 'categories' ]),
						new Xml\Tag\EnableAutoDiscounts([ 'global' => true ]),
						new Xml\Tag\Root([ 'name' => 'offers' ]),
						new Xml\Tag\Base([ 'name' => 'gifts' ]),
						new Xml\Tag\Base([ 'name' => 'promos' ]),
					],
				]),
			],
		]);

		if ($this->isSupportDeliveryOptions())
		{
			$rootChidren = $result->getChildren();
			$shopTag = reset($rootChidren);

			$shopTag->addChild(new Xml\Tag\DeliveryOptions([
				'multiple' => true,
				'attributes' => [
					new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
					new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
					new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
				]
			]), -4);
			$shopTag->addChild(new Xml\Tag\PickupOptions([
				'multiple' => false,
				'attributes' => [
					new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
					new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
					new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
				]
			]), -4);
		}

		return $result;
	}

	public function getCategoryParentName()
	{
		return 'categories';
	}

	public function getCategory()
	{
		return new Xml\Tag\Base([
			'name' => 'category',
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'required' => true, 'primary' => true]),
				new Xml\Attribute\Base(['name' => 'parentId']),
			],
		]);
	}

	public function getCurrencyParentName()
	{
		return 'currencies';
	}

	public function getCurrency()
	{
		return new Xml\Tag\Base([
			'name' => 'currency',
			'empty_value' => true,
			'attributes' => [
				new Xml\Attribute\Base(['name' => 'id', 'value_type' => 'currency', 'required' => true, 'primary' => true]),
				new Xml\Attribute\Base(['name' => 'rate', 'required' => true]),
			],
		]);
	}

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
        $isUrlRequired = ($type === Promo\Table::PROMO_TYPE_BONUS_CARD);

        // overview

        $result->addChild(new Xml\Tag\Base(['name' => 'start-date', 'value_type' => Type\Manager::TYPE_DATE, 'required' => $isDateRequired]));
        $result->addChild(new Xml\Tag\Base(['name' => 'end-date', 'value_type' => Type\Manager::TYPE_DATE, 'required' => $isDateRequired]));
        $result->addChild(new Xml\Tag\Base(['name' => 'description', 'value_type' => Type\Manager::TYPE_HTML, 'max_length' => 500]));
        $result->addChild(new Xml\Tag\Base(['name' => 'url', 'value_type' => Type\Manager::TYPE_URL, 'required' => $isUrlRequired]));

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

	public function getOfferParentName()
	{
		return 'offers';
	}

	/**
	 * @return Xml\Tag\Base
	 */
	public function getOffer()
	{
		return new Xml\Tag\Offer([
			'name' => 'offer',
			'required' => true,
			'visible' => true,
			'attributes' => [
				new Xml\Attribute\Id(['required' => true]),
				new Xml\Attribute\Type(['required' => true]),
				new Xml\Attribute\Available(['value_type' => 'boolean', 'visible' => true]),
				new Xml\Attribute\Base(['name' => 'bid', 'value_type' => 'number']),
				new Xml\Attribute\Base(['name' => 'group_id', 'value_type' => 'number']),
			],
			'children' => array_merge(
				$this->getOfferDefaultChildren('prolog', [
					'picture' => [ 'required' => true ]
				]),
				[
					new Xml\Tag\Vendor(['required' => true]),
					new Xml\Tag\Model(['required' => true]),
					new Xml\Tag\Base(['name' => 'vendorCode', 'visible' => true]),
					new Xml\Tag\Base(['name' => 'typePrefix', 'visible' => true]),
				],
				$this->getOfferDefaultChildren('epilog', [
					'barcode' => [ 'visible' => true ]
				])
			)
		]);
	}

	protected function getOfferDefaultChildren($place, $overrides = null, $sort = null, $excludeList = null)
	{
		$result = [];

		switch ($place)
		{
			case 'prolog':
				$result = [
					new Xml\Tag\Url(['required' => true]),
					new Xml\Tag\Price(['required' => true]),
					new Xml\Tag\OldPrice(),
					new Xml\Tag\PurchasePrice(),
					new Xml\Tag\EnableAutoDiscounts(),
					new Xml\Tag\CurrencyId(['required' => true]),
					new Xml\Tag\CategoryId(['required' => true]),
					new Xml\Tag\Picture(['multiple' => true, 'visible' => true]),
					new Xml\Tag\Base(['name' => 'delivery', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'pickup', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'store', 'value_type' => 'boolean']),
				];

				if ($this->isSupportDeliveryOptions())
				{
					array_splice($result, -2, 0, [
						new Xml\Tag\DeliveryOptions([
							'multiple' => true,
							'attributes' => [
								new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
								new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
								new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
							]
						]),
						new Xml\Tag\PickupOptions([
							'multiple' => false,
							'attributes' => [
								new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
								new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
								new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
							]
						]),
					]);
				}
			break;

			case 'epilog':
				$result = [
					new Xml\Tag\Description(['visible' => true]),
					new Xml\Tag\SalesNotes(),
					new Xml\Tag\MinQuantity(),
					new Xml\Tag\Base(['name' => 'manufacturer_warranty', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'country_of_origin']),
					new Xml\Tag\Base(['name' => 'adult', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'barcode', 'multiple' => true, 'value_type' => Type\Manager::TYPE_BARCODE]),
					new Xml\Tag\Cpa(),
					new Xml\Tag\Param([
						'multiple' => true,
						'visible' => true,
						'attributes' => [
							new Xml\Attribute\Base(['name' => 'name', 'required' => true, 'visible' => true]),
							new Xml\Attribute\Base(['name' => 'unit']),
						],
					]),
					new Xml\Tag\Expiry(),
					new Xml\Tag\Weight(),
					new Xml\Tag\Dimensions(),
					new Xml\Tag\Base(['name' => 'downloadable', 'value_type' => 'boolean']),
					new Xml\Tag\Age([
						'attributes' => [
							new Xml\Attribute\Base(['name' => 'unit', 'visible' => true]),
						],
					]),
					new Xml\Tag\Condition([
						'max_length' => 3000,
						'value_type' => Type\Manager::TYPE_HTML,
						'attributes' => [
							new Xml\Attribute\ConditionType(['required' => true, 'value_type' => Type\Manager::TYPE_CONDITION])
						],
					]),
					new Xml\Tag\CreditTemplate(['multiple' => true]),
					new Xml\Tag\Count(),
				];
			break;
		}

		$this->overrideTags($result, $overrides);
		$this->excludeTags($result, $excludeList);
		$this->sortTags($result, $sort);

		return $result;
	}

	protected function overrideTags($tags, $overrides)
	{
		if ($overrides !== null)
		{
			/** @var \Yandex\Market\Export\Xml\Tag\Base $tag */
			foreach ($tags as $tag)
			{
				$tagName = $tag->getName();

				if (isset($overrides[$tagName]))
				{
					$tag->extendParameters($overrides[$tagName]);
				}
			}
		}
	}

	protected function sortTags(&$tags, $sort)
	{
		if ($sort !== null)
		{
			$fullSort = [];
			$nextSortIndex = 10;

			foreach ($tags as $tag)
			{
				$tagId = $tag->getId();
				$fullSort[$tagId] = isset($sort[$tagId]) ? $sort[$tagId] : $nextSortIndex;

				$nextSortIndex += 10;
			}

			uasort($tags, function($tagA, $tagB) use ($fullSort) {
				$tagAId = $tagA->getId();
				$tagBId = $tagB->getId();
				$tagASort = $fullSort[$tagAId];
				$tagBSort = $fullSort[$tagBId];

				if ($tagASort === $tagBSort) { return 0; }

				return ($tagASort < $tagBSort ? -1 : 1);
			});
		}
	}

	protected function excludeTags(&$tags, $excludeList)
	{
		if ($excludeList !== null)
		{
			foreach ($tags as $tagIndex => $tag)
			{
				$tagName = $tag->getName();

				if (isset($excludeList[$tagName]))
				{
					unset($tags[$tagIndex]);
				}
			}
		}
	}

	protected function removeChildTags(Xml\Tag\Base $tag, $tagNameList)
	{
		foreach ($tagNameList as $tagName)
		{
			$childTag = $tag->getChild($tagName);

			if ($childTag)
			{
				$tag->removeChild($childTag);
			}
		}
	}
}
