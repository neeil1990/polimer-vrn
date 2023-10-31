<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Yandex\Market\Export\CollectionProduct;

interface StrategyFilterable
{
	public function setProductCollection(CollectionProduct\Collection $productCollection);
}