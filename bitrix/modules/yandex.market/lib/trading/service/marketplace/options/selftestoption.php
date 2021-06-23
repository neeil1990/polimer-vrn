<?php

namespace Yandex\Market\Trading\Service\Marketplace\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class SelfTestOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function isOutOfStock()
	{
		return (string)$this->getValue('OUT_OF_STOCK') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'OUT_OF_STOCK' => [
				'TYPE' => 'boolean',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SELF_TEST_OUT_OF_STOCK'),
				'SORT' => 2300,
			],
		];
	}
}