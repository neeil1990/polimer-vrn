<?php

namespace Yandex\Market\Export\Entity\Text;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		return [];
	}

	public function getFields(array $context = [])
	{
		return null;
	}

	public function getControl()
	{
		return Market\Export\Entity\Manager::CONTROL_TEXT;
	}

	protected function getLangPrefix()
	{
		return 'TEXT_';
	}
}