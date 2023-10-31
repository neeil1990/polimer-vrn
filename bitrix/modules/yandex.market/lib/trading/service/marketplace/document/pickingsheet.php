<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class PickingSheet extends TradingService\Reference\Document\AbstractDocument
{
	use Market\Reference\Concerns\HasMessage { getMessage as protected getMessageInternal; }

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return self::getMessageInternal('TITLE' . $suffix);
	}

	public function getMessage($type)
	{
		$suffix = Market\Data\TextString::toUpper($type);

		return self::getMessageInternal($suffix, null, '');
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
	}

	public function render(array $items, array $settings = [])
	{
		$parameters = [
			'ITEMS' => $items,
		];
		$parameters += $settings;

		return $this->renderComponent('pickingsheet', $parameters);
	}
}