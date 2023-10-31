<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Export;

class ListComponent extends DetailComponent
{
	use Concerns\HasMessage;

	public function key()
	{
		return 'iblock.LIST_PAGE_SHOW';
	}

	public function title()
	{
		return self::getMessage('TITLE');
	}

	protected function parameterMap()
	{
		return [
			Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY => [
				'LIST_PROPERTY_CODE',
				'PROPERTY_CODE',
			],
			Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY => [
				'LIST_OFFERS_PROPERTY_CODE',
				'OFFERS_PROPERTY_CODE',
			],
		];
	}

	protected function iblockPageTemplate(array $iblock)
	{
		return (string)($iblock['SECTION_PAGE_URL'] ?: $iblock['LIST_PAGE_URL'] ?: $iblock['DETAIL_PAGE_URL']);
	}
}