<?php

namespace Yandex\Market\Export\Xml\Format\YandexMarket;

use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

class VendorModel extends Xml\Format\Reference\Base
{
	use Xml\Format\Reference\HasCategory;
	use Xml\Format\Reference\HasCollection;
	use Xml\Format\Reference\HasCurrency;
	use Xml\Format\Reference\HasPromo;

	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/partnermarket/export/vendor-model.html';
	}

	public function getSupportedFields()
	{
		return [
			'SHOP_DATA',
			'ENABLE_CPA',
			'ENABLE_AUTO_DISCOUNTS',
		];
	}

	public function getType()
	{
		return 'vendor.model';
	}

	public function isSupportDeliveryOptions()
	{
		return true;
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
						new Xml\Tag\Base([ 'name' => 'collections' ]),
						new Xml\Tag\Base([ 'name' => 'gifts' ]),
						new Xml\Tag\Base([ 'name' => 'promos' ]),
					],
				]),
			],
		]);

		if ($this->isSupportDeliveryOptions())
		{
			$rootChildren = $result->getChildren();
			$shopTag = reset($rootChildren);

			$shopTag->addChild(new Xml\Tag\DeliveryOptions([
				'multiple' => true,
				'attributes' => [
					new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
					new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
					new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
				]
			]), -5);
			$shopTag->addChild(new Xml\Tag\PickupOptions([
				'multiple' => false,
				'attributes' => [
					new Xml\Attribute\Base(['name' => 'cost', 'required' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
					new Xml\Attribute\Base(['name' => 'days', 'required' => true, 'value_type' => Type\Manager::TYPE_DAYS]),
					new Xml\Attribute\Base(['name' => 'order-before', 'visible' => true, 'value_type' => Type\Manager::TYPE_NUMBER]),
				]
			]), -5);
		}

		return $result;
	}

	public function getOfferParentName()
	{
		return 'offers';
	}

	/** @return Xml\Tag\Base */
	public function getOffer()
	{
		return new Xml\Tag\Offer([
			'name' => 'offer',
			'required' => true,
			'visible' => true,
			'attributes' => [
				new Xml\Attribute\Id(['required' => true]),
				new Xml\Attribute\Type(['required' => true]),
				new Xml\Attribute\Available(['value_type' => 'boolean', 'visible' => true, 'preselect' => true]),
				new Xml\Attribute\Base(['name' => 'bid', 'value_type' => 'number']),
				new Xml\Attribute\GroupId(['preselect' => true]),
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
				$result = array_filter([
					new Xml\Tag\Url(['preselect' => true]),
					new Xml\Tag\Price(['required' => true]),
					new Xml\Tag\OldPrice(),
					new Xml\Tag\PurchasePrice(),
					new Xml\Tag\PurchasePrice(['name' => 'cofinance_price']),
					new Xml\Tag\EnableAutoDiscounts(),
					new Xml\Tag\Vat(),
					new Xml\Tag\CurrencyId(['required' => true]),
					new Xml\Tag\CategoryId(['required' => true]),
					$this->getCollectionId(),
					new Xml\Tag\Picture(['multiple' => true, 'visible' => true, 'preselect' => true]),
					new Xml\Tag\Base(['name' => 'video', 'value_type' => 'file']),
					new Xml\Tag\Base(['name' => 'delivery', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'pickup', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'store', 'value_type' => 'boolean']),
					new Xml\Tag\CargoTypes(),
				]);

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
					new Xml\Tag\Description(['visible' => true, 'preselect' => true]),
					new Xml\Tag\SetIds(),
					new Xml\Tag\SalesNotes(),
					new Xml\Tag\MinQuantity(),
					new Xml\Tag\StepQuantity(),
					new Xml\Tag\Base(['name' => 'manufacturer_warranty', 'value_type' => 'boolean']),
					new Xml\Tag\Base(['name' => 'country_of_origin']),
					new Xml\Tag\Base(['name' => 'adult', 'value_type' => 'boolean']),
					new Xml\Tag\Barcode(['multiple' => true, 'preselect' => true]),
					new Xml\Tag\Cpa(),
					new Xml\Tag\Param([
						'multiple' => true,
						'visible' => true,
						'preselect' => true,
						'attributes' => [
							new Xml\Attribute\ParamName(['required' => true, 'visible' => true, 'preselect' => true]),
							new Xml\Attribute\ParamUnit(['preselect' => true]),
						],
					]),
					new Xml\Tag\Expiry(),
					new Xml\Tag\Weight(['preselect' => true]),
					new Xml\Tag\Expiry(['name' => 'period-of-validity-days', 'value_type' => Type\Manager::TYPE_PERIOD]),
					new Xml\Tag\Base(['name' => 'comment-validity-days']),
					new Xml\Tag\Expiry(['name' => 'service-life-days', 'value_type' => Type\Manager::TYPE_PERIOD]),
					new Xml\Tag\Base(['name' => 'comment-life-days']),
					new Xml\Tag\Expiry(['name' => 'warranty-days', 'value_type' => Type\Manager::TYPE_PERIOD]),
					new Xml\Tag\Base(['name' => 'comment-warranty']),
					new Xml\Tag\Base(['name' => 'certificate']),
					new Xml\Tag\Dimensions(['preselect' => true]),
					new Xml\Tag\Base(['name' => 'downloadable', 'value_type' => 'boolean']),
					new Xml\Tag\Age([
						'attributes' => [
							new Xml\Attribute\Base(['name' => 'unit', 'visible' => true]),
						],
					]),
					new Xml\Tag\Condition([
						'critical' => true,
						'tree' => true,
						'attributes' => [
							new Xml\Attribute\ConditionType([ 'required' => true ]),
						],
						'children' => [
							new Xml\Tag\ConditionQuality([ 'required' => true ]),
							new Xml\Tag\ConditionReason([ 'required' => true ]),
						],
					]),
					new Xml\Tag\CreditTemplate(['multiple' => true]),
					new Xml\Tag\Base(['name' => 'tn-ved-code', 'wrapper_name' => 'tn-ved-codes', 'multiple' => true, 'value_type' => Type\Manager::TYPE_TN_VED_CODE]),
					new Xml\Tag\Disabled(),
					new Xml\Tag\Count(),
					new Xml\Tag\Base(['name' => 'box-count', 'value_type' => Type\Manager::TYPE_NUMBER]),
					new Xml\Tag\Base([
						'name' => 'price-option',
						'multiple' => true,
						'max_count' => 5,
						'tree' => true,
						'children' => [
							new Xml\Tag\PriceOption\MinQuantity([
								'visible' => true,
								'attributes' => [
									new Xml\Attribute\PriceOption\MinQuantityUnit(['required' => true]),
								],
							]),
							new Xml\Tag\Base(['name' => 'min-order-sum', 'value_type' => Type\Manager::TYPE_NUMBER]),
							new Xml\Tag\Base(['name' => 'shipment-days', 'value_type' => Type\Manager::TYPE_NUMBER]),
							new Xml\Tag\PriceOption\Price([ 'visible' => true ]),
							new Xml\Tag\PriceOption\OldPrice(),
							new Xml\Tag\PriceOption\Discount([
								'attributes' => [
									new Xml\Attribute\PriceOption\DiscountUnit(['required' => true]),
								],
							]),
						],
					]),
					new Xml\Tag\Restrictions([
						'name' => 'restrictions',
						'wholesalePrice' => 'price-option',
						'tree' => true,
						'children' => [
							new Xml\Tag\Base([
								'name' => 'clients',
								'required' => true,
								'tree' => true,
								'children' => [
									new Xml\Tag\Base(['name' => 'b2c', 'value_type' => Type\Manager::TYPE_BOOLEAN, 'default_value' => true, 'required' => true]),
									new Xml\Tag\Base(['name' => 'b2b', 'value_type' => Type\Manager::TYPE_BOOLEAN, 'default_value' => false, 'required' => true]),
								],
							]),
							new Xml\Tag\Base([
								'name' => 'trading',
								'required' => true,
								'tree' => true,
								'children' => [
									new Xml\Tag\Base(['name' => 'retail', 'value_type' => Type\Manager::TYPE_BOOLEAN, 'default_value' => true, 'required' => true]),
									new Xml\Tag\Base(['name' => 'wholesale', 'value_type' => Type\Manager::TYPE_BOOLEAN, 'default_value' => false, 'required' => true]),
								],
							]),
						],
					]),
				];
			break;
		}

		$this->overrideTags($result, $overrides);
		$this->excludeTags($result, $excludeList);
		$this->sortTags($result, $sort);

		return $result;
	}
}
