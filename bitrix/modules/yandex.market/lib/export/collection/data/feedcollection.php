<?php
namespace Yandex\Market\Export\Collection\Data;

use Yandex\Market\Export\CollectionProduct;
use Yandex\Market\Reference\Assert;

class FeedCollection
{
	protected $fields;
	protected $productCollection;
	protected $context;

	public function __construct(array $fields, CollectionProduct\Collection $productCollection)
	{
		Assert::notNull($fields['ID'], 'fields[ID]');

		$this->fields = $fields;
		$this->productCollection = $productCollection;
	}

	public function getId()
	{
		return $this->fields['ID'];
	}

	public function getPrimary()
	{
		return !empty($this->fields['PRIMARY']) ? $this->fields['PRIMARY'] : $this->fields['ID'];
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getProductCollection()
	{
		return $this->productCollection;
	}
}