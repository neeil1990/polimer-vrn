<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market\Trading\Entity as TradingEntity;

class OrderRegistry extends TradingEntity\Sale\OrderRegistry
{
	public function getAdminListUrl(TradingEntity\Reference\Platform $platform)
	{
		if (!$platform->isInstalled())
		{
			$message = static::getLang('TRADING_ENTITY_SALE_ORDER_REGISTRY_PLATFORM_NOT_INSTALLED');
			throw new Main\SystemException($message);
		}

		if (Main\Context::getCurrent()->getRequest()->isAdminSection())
		{
			return parent::getAdminListUrl($platform);
		}

		return '/shop/orders/?' . http_build_query([
			'apply_filter' => 'Y',
			'SOURCE_ID' => $platform->getId(),
		]);
	}

	protected function makeOrder(Sale\OrderBase $internalOrder, $eventProcessing = null)
	{
		return new Order($this->environment, $internalOrder, $eventProcessing);
	}
}