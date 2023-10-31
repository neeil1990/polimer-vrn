<?php

namespace Yandex\Market\Api\Partner\BusinessInfo;

use Yandex\Market;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Api\Reference\HasOauthConfiguration;
use Yandex\Market\Psr\Log\LoggerInterface;

/** @deprecated */
class Facade
{
	use Concerns\HasMessage;

	const CACHE_TTL = 86400;

	public static function businessId(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		trigger_error(sprintf('use %s for get campaign business', Market\Api\Campaigns\Facade::class), E_USER_DEPRECATED);

		return Market\Api\Campaigns\Facade::businessId($options, $logger);
	}

	public static function warehouseId($campaignId, HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		trigger_error(sprintf('use %s for get campaign warehouse', Market\Api\Business\Warehouses\Facade::class), E_USER_DEPRECATED);

		return Market\Api\Business\Warehouses\Facade::primaryWarehouse($options, $logger);
	}
}