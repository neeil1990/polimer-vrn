<?php
namespace Yandex\Market\Export\Run\Steps\CollectionOffer;

use Yandex\Market\Data\Type;
use Yandex\Market\Export\Collection;

class CollectState
{
	/** @var string */
	public $runAction;
	/** @var Type\CanonicalDateTime */
	public $initTime;
	/** @var array|null */
	public $changes;
	/** @var Collection\Model $collection */
	public $collection;
	/** @var Collection\Data\FeedCollection */
	public $feedCollection;
	/** @var array */
	public $sourceMap;
	/** @var array */
	public $sourceSelect;
	/** @var array */
	public $querySelect;
	/** @var array */
	public $queryFilter;
	/** @var array */
	public $context;
}