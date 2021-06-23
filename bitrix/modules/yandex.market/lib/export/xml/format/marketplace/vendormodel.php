<?php

namespace Yandex\Market\Export\Xml\Format\Marketplace;

use Yandex\Market\Export\Xml;
use Yandex\Market\Type;

class VendorModel extends Xml\Format\YandexMarket\VendorModel
{
	public function getDocumentationLink()
	{
		return 'https://yandex.ru/support/marketplace/catalog/yml-simple.html';
	}

	public function getOffer()
	{
		$tag = parent::getOffer();

		$this->overrideTags($tag->getChildren(), [
			'picture' => [ 'required' => false ],
			'model' => [ 'name' => 'name' ],
			'country_of_origin' => [ 'visible' => true ],
			'dimensions' => [ 'visible' => true ],
			'weight' => [ 'visible' => true ],
		]);

		$tag->addChild(new Xml\Tag\Vat(), 'enable_auto_discounts');
		$tag->addChild(new Xml\Tag\Base(['name' => 'manufacturer', 'visible' => true]), 'manufacturer_warranty');

		$tag->addChildren([
			new Xml\Tag\Expiry(['name' => 'period-of-validity-days']),
			new Xml\Tag\Base(['name' => 'comment-validity-days']),
			new Xml\Tag\Expiry(['name' => 'service-life-days']),
			new Xml\Tag\Base(['name' => 'comment-life-days']),
			new Xml\Tag\Expiry(['name' => 'warranty-days']),
			new Xml\Tag\Base(['name' => 'comment-warranty']),
			new Xml\Tag\Base(['name' => 'certificate']),
		], 'dimensions');

		$this->removeChildTags($tag, ['count']); // add below for sorting
		$tag->addChildren([
			new Xml\Tag\Base(['name' => 'tn-ved-code', 'wrapper_name' => 'tn-ved-codes', 'multiple' => true, 'value_type' => Type\Manager::TYPE_TN_VED_CODE]),
			new Xml\Tag\ShopSku(['required' => true]),
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
			new Xml\Tag\Disabled(),
			new Xml\Tag\Count(),
			new Xml\Tag\Base(['name' => 'transport-unit', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'min-delivery-pieces', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'quantum', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'leadtime', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'box-count', 'value_type' => Type\Manager::TYPE_NUMBER]),
			new Xml\Tag\Base(['name' => 'delivery-weekday', 'wrapper_name' => 'delivery-weekdays', 'multiple' => true, 'value_type' => Type\Manager::TYPE_WEEKDAY]),
		]);

		$this->removeChildTags($tag, ['condition', 'credit-template', 'purchase_price']);

		return $tag;
	}
}