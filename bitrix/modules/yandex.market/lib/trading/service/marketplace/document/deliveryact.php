<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class DeliveryAct extends TradingService\Reference\Document\AbstractDocument
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_DELIVERY_ACT' . $suffix);
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
	}

	public function render(array $items, array $settings = [])
	{
		$parameters = [
			'ITEMS' => $items,
			'CLIENT_NAME' => $this->provider->getOptions()->getValue('COMPANY_LEGAL_NAME'),
		];
		$parameters += $settings;

		return $this->renderComponent('deliveryact', $parameters);
	}
}