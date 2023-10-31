<?php
namespace Yandex\Market\SalesBoost\Run\Steps\Collector;

use Yandex\Market\Export;
use Yandex\Market\SalesBoost;
use Yandex\Market\Data;

class State
{
	// -- Processor

	/** @var string */
	public $runAction;
	/** @var array */
	public $changes;
	/** @var array|null */
	public $selectedBoosts;
	/** @var Data\Type\CanonicalDateTime */
	public $initTime;

	// -- Models

	/** @var SalesBoost\Setup\Model */
	public $boost;
	/** @var SalesBoost\Product\Model */
	public $boostProduct;
	/** @var Export\Filter\Model */
	public $exportFilter;

	// -- Query

	/** @var array */
	public $sourceMap;
	/** @var array */
	public $sourceSelect;
	/** @var array */
	public $querySelect;
	/** @var array */
	public $queryFilter;

	// -- Elements

	/** @var array */
	public $elements;
	/** @var array */
	public $elementsValues;

	// -- Common

	/** @var array */
	public $context;
}