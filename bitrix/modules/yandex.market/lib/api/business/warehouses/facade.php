<?php

namespace Yandex\Market\Api\Business\Warehouses;

use Yandex\Market;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Api\Reference\HasOauthConfiguration;
use Yandex\Market\Psr\Log\LoggerInterface;
use Bitrix\Main;

class Facade
{
	use Concerns\HasMessage;

	const CACHE_TTL = 86400;

	public static function primaryWarehouse(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$businessId = Market\Api\Campaigns\Facade::businessId($options);
		$campaignId = $options->getCampaignId();
		$data = static::warehousesData($businessId, $options, $logger);

		return is_array($data[$campaignId]) ? $data[$campaignId] : [ $data[$campaignId] ];
	}

	public static function storeGroup(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$businessId = Market\Api\Campaigns\Facade::businessId($options);
		$campaignId = $options->getCampaignId();
		$data = static::warehousesData($businessId, $options, $logger);

		if (!is_array($data[$campaignId])) { return null; }

		list($primaryWarehouse) = $data[$campaignId];
		$result = [];

		foreach ($data as $campaignId => $row)
		{
			if (is_array($row) && $row[0] === $primaryWarehouse)
			{
				$result[] = $campaignId;
			}
		}

		return $result;
	}

	protected static function warehousesData($businessId, HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = static::CACHE_TTL;
		$cacheKey = 'BUSINESS_WAREHOUSES_' . $businessId;

		if ($cache->read($cacheTtl, $cacheKey, Market\Trading\Setup\Table::getTableName()))
		{
			$result = $cache->get($cacheKey);
		}
		else
		{
			$response = static::fetchWarehouses($businessId, $options, $logger);
			$result = [];
			$warehouses = $response->getWarehouses();
			$warehouseGroups = $response->getWarehouseGroups();

			if ($warehouses !== null)
			{
				/** @var Model\Warehouse $warehouse */
				foreach ($warehouses as $warehouse)
				{
					$result[$warehouse->getCampaignId()] = $warehouse->getId();
				}
			}

			if ($warehouseGroups !== null)
			{
				/** @var Model\WarehouseGroup $warehouseGroup */
				foreach ($warehouseGroups as $warehouseGroup)
				{
					/** @var Model\Warehouse $warehouse */
					foreach ($warehouseGroup->getWarehouses() as $warehouse)
					{
						$result[$warehouse->getCampaignId()] = [
							$warehouseGroup->getMainWarehouse()->getId(),
							$warehouseGroup->getMainWarehouse()->getCampaignId(),
						];
					}
				}
			}

			$cache->set($cacheKey, $result);
		}

		return $result;
	}

	protected static function fetchWarehouses($businessId, HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$request = new Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setBusinessId($businessId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getMessage('FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var Response */
		return $sendResult->getResponse();
	}
}