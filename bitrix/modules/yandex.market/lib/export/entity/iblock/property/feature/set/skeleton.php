<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

use Yandex\Market;

abstract class Skeleton implements Set
{
	protected $context;

	public function __construct(array $context)
	{
		$this->context = $context;
	}

	public function deprecated()
	{
		return false;
	}

	protected function sourcesMap()
	{
		return array_filter([
			Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY => $this->context['IBLOCK_ID'],
			Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY => $this->context['HAS_OFFER'] ? $this->context['OFFER_IBLOCK_ID'] : null,
		]);
	}
}