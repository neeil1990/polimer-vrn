<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Yandex\Market\Trading\Entity\Reference as EntityReference;
use Bitrix\Main;
use Bitrix\Sale;

class OrderRegistry extends EntityReference\OrderRegistry
{
	use Market\Reference\Concerns\HasLang;

	/** @var Environment */
	protected $environment;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/**
	 * @noinspection NotOptimalIfConditionsInspection
	 * @noinspection PhpCastIsUnnecessaryInspection
	 */
	public static function useAccountNumber()
	{
		if (Market\Config::getOption('trading_order_use_id', 'N') === 'Y') { return false; }

		if (
			Main\Loader::includeModule('sale')
			&& class_exists(Sale\Integration\Numerator\NumeratorOrder::class)
		)
		{
			return Sale\Integration\Numerator\NumeratorOrder::isUsedNumeratorForOrder();
		}

		return (string)Main\Config\Option::get('sale', 'account_number_template') !== '';
	}

	public static function getOrderAccountNumber(Sale\OrderBase $order)
	{
		if (static::useAccountNumber())
		{
			$result = $order->getField('ACCOUNT_NUMBER');
		}
		else
		{
			$result = $order->getId();
		}

		return $result;
	}

	/**
	 * @param int $orderId
	 *
	 * @return array{TRADING_PLATFORM_ID: int, EXTERNAL_ORDER_ID: string, SETUP_ID: int|null}|null
	 */
	public static function searchPlatform($orderId)
	{
		$result = null;

		$query = Sale\TradingPlatform\OrderTable::getList([
			'filter' => [
				'=ORDER_ID' => $orderId,
			],
			'select' => [ 'TRADING_PLATFORM_ID', 'EXTERNAL_ORDER_ID', 'PARAMS' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = [
				'TRADING_PLATFORM_ID' => (int)$row['TRADING_PLATFORM_ID'],
				'EXTERNAL_ORDER_ID' => (string)$row['EXTERNAL_ORDER_ID'],
				'SETUP_ID' => isset($row['PARAMS']['SETUP_ID']) ? (int)$row['PARAMS']['SETUP_ID'] : null,
			];
		}

		return $result;
	}

	/**
	 * @return Sale\OrderBase
	 */
	public static function getOrderClassName()
	{
		if (class_exists(Sale\Registry::class))
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
			$result = $registry->getOrderClassName();
		}
		else
		{
			$result = Sale\Order::class;
		}

		return $result;
	}

	/**
	 * @return Sale\BasketBase
	 */
	public static function getBasketClassName()
	{
		if (class_exists(Sale\Registry::class))
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
			$result = $registry->getBasketClassName();
		}
		else
		{
			$result = Sale\Basket::class;
		}

		return $result;
	}

	/**
	 * @return Sale\OrderStatus
	 */
	public static function getOrderStatusClassName()
	{
		if (method_exists(Sale\Registry::class, 'getOrderStatusClassName'))
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
			$result = $registry->getOrderStatusClassName();
		}
		else
		{
			$result = Sale\OrderStatus::class;
		}

		return $result;
	}

	/**
	 * @return Sale\DeliveryStatus
	 */
	public static function getDeliveryStatusClassName()
	{
		if (method_exists(Sale\Registry::class, 'getDeliveryStatusClassName'))
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
			$result = $registry->getDeliveryStatusClassName();
		}
		else
		{
			$result = Sale\DeliveryStatus::class;
		}

		return $result;
	}

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getAdminListUrl(EntityReference\Platform $platform)
	{
		if (!$platform->isInstalled())
		{
			$message = static::getLang('TRADING_ENTITY_SALE_ORDER_REGISTRY_PLATFORM_NOT_INSTALLED');
			throw new Main\SystemException($message);
		}

		return Market\Ui\Admin\Path::getPageUrl('sale_order', [
			'lang' => LANGUAGE_ID,
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
			'filter_source' => $platform->getId(),
		]);
	}

	public function createOrder($siteId, $userId, $currency)
	{
		if (!Market\Data\Currency::isCalculatable($currency))
		{
			$message = static::getLang('TRADING_ENTITY_SALE_ORDER_REGISTRY_CURRENCY_NOT_CALCULATABLE', [ '#CURRENCY#' => $currency ]);
			throw new Main\SystemException($message);
		}

		$orderClassName = static::getOrderClassName();
		$internalOrder = $orderClassName::create($siteId, $userId, $currency);

		return $this->makeOrder($internalOrder);
	}

	public function loadOrderList($orderIds)
	{
		if (empty($orderIds)) { return []; }

		$result = [];
		$internalOrders = [];
		$orderClassName = static::getOrderClassName();

		if (\method_exists($orderClassName, 'loadByFilter'))
		{
			$internalOrders = (array)$orderClassName::loadByFilter([
				'filter' => [ '=ID' => $orderIds ]
			]);
		}
		else
		{
			foreach ($orderIds as $orderId)
			{
				$internalOrder = $orderClassName::load($orderId);

				if ($internalOrder !== null)
				{
					$internalOrders[] = $internalOrder;
				}
			}
		}

		foreach ($internalOrders as $internalOrder)
		{
			$order = $this->makeOrder($internalOrder);
			$orderId = $order->getId();

			$result[$orderId] = $order;
		}

		return $result;
	}

	public function loadOrder($orderId)
	{
		if (Listener::hasOrder($orderId))
		{
			$internalOrder = Listener::getOrder($orderId);
			$processingStatus = Listener::getOrderState($orderId);
		}
		else
		{
			$orderClassName = static::getOrderClassName();
			$internalOrder = $orderClassName::load($orderId);
			$processingStatus = null;
		}

		if ($internalOrder === null)
		{
			throw new Main\ObjectNotFoundException();
		}

		return $this->makeOrder($internalOrder, $processingStatus);
	}

	protected function makeOrder(Sale\OrderBase $internalOrder, $eventProcessing = null)
	{
		return new Order($this->environment, $internalOrder, $eventProcessing);
	}

	public function isExistMarker($orderId, $code, $condition = null)
	{
		$marker = $this->environment->getMarker();

		if (!Listener::hasOrder($orderId) && $marker->hasExternalEntity())
		{
			$result = ($marker->getMarkerId($orderId, $code, $condition) !== null);
		}
		else
		{
			$order = $this->loadOrder($orderId);
			$result = $order->isExistMarker($code, $condition);
		}

		return $result;
	}

	public function searchList($externalIds, EntityReference\Platform $platform, $useAccountNumber = null)
	{
		$result = [];
		$select = [ 'ORDER_ID', 'EXTERNAL_ORDER_ID' ];
		$setupIds = $this->platformSetupIds($platform);
		$setupMap = $setupIds !== null ? array_flip($setupIds) : null;

		if ($useAccountNumber === null)
		{
			$useAccountNumber = static::useAccountNumber();
		}

		if ($useAccountNumber)
		{
			$select['ACCOUNT_NUMBER'] = 'ORDER.ACCOUNT_NUMBER';
		}

		if ($setupMap !== null)
		{
			$select[] = 'PARAMS';
		}

		foreach (array_chunk($externalIds, 500) as $externalChunk)
		{
			$query = Sale\TradingPlatform\OrderTable::getList([
				'filter' => [
					'=TRADING_PLATFORM_ID' => $platform->getId(),
					'=EXTERNAL_ORDER_ID' => $externalChunk,
				],
				'select' => $select
			]);

			while ($row = $query->fetch())
			{
				if (
					$setupMap !== null
					&& isset($row['PARAMS']['SETUP_ID'])
					&& !isset($setupMap[$row['PARAMS']['SETUP_ID']])
				)
				{
					continue;
				}

				$externalOrderId = $row['EXTERNAL_ORDER_ID'];
				$accountNumber = $row['ORDER_ID'];

				if ($useAccountNumber && (string)$row['ACCOUNT_NUMBER'] !== '')
				{
					$accountNumber = $row['ACCOUNT_NUMBER'];
				}

				$result[$externalOrderId] = $accountNumber;
			}
		}

		return $result;
	}

	protected function platformSetupIds(EntityReference\Platform $platform)
	{
		$setupId = $platform->getSetupId();
		$groupIds = $platform->getGroupSetupIds();

		if ($setupId === null && $groupIds === null) { return null; }

		return array_merge(
			(array)$setupId,
			(array)$groupIds
		);
	}

	public function search($externalId, EntityReference\Platform $platform, $useAccountNumber = null)
	{
		$map = $this->searchList([$externalId], $platform, $useAccountNumber);

		return isset($map[$externalId]) ? $map[$externalId] : null;
	}

	public function searchBrokenList($externalIds, EntityReference\Platform $platform, $useAccountNumber = null)
	{
		$result = [];
		$orderClassName = static::getOrderClassName();
		$xmlIdMap = $this->makeOrderXmlIdMap($externalIds, $platform);
		$select = [ 'ID', 'XML_ID' ];

		if ($useAccountNumber === null)
		{
			$useAccountNumber = static::useAccountNumber();
		}

		if ($useAccountNumber)
		{
			$select[] = 'ACCOUNT_NUMBER';
		}

		$parameters = [
			'filter' => [ '=XML_ID' => array_keys($xmlIdMap) ],
			'select' => $select,
			'limit' => count($xmlIdMap),
		];
		$query = method_exists($orderClassName, 'getList')
			? $orderClassName::getList($parameters)
			: Sale\Internals\OrderTable::getList($parameters);

		while ($row = $query->fetch())
		{
			if (!isset($xmlIdMap[$row['XML_ID']])) { continue; }

			$externalId = $xmlIdMap[$row['XML_ID']];
			$accountNumber = $row['ID'];

			if ($useAccountNumber && (string)$row['ACCOUNT_NUMBER'] !== '')
			{
				$accountNumber = $row['ACCOUNT_NUMBER'];
			}

			$result[$externalId] = $accountNumber;
		}

		return $result;
	}

	public function searchBroken($externalId, EntityReference\Platform $platform, $useAccountNumber = null)
	{
		$map = $this->searchBrokenList([$externalId], $platform, $useAccountNumber);

		return isset($map[$externalId]) ? $map[$externalId] : null;
	}

	protected function makeOrderXmlIdMap($externalIds, EntityReference\Platform $platform)
	{
		$result = [];

		foreach ((array)$externalIds as $externalId)
		{
			$xmlId = $platform->getOrderXmlId($externalId);

			$result[$xmlId] = $externalId;
		}

		return $result;
	}

	public function suggestExternalIds($value, $field, EntityReference\Platform $platform)
	{
		$result = [];
		$select = [ 'EXTERNAL_ORDER_ID' ];
		$search = $field;

		if ($field === 'ACCOUNT_NUMBER')
		{
			$search = static::useAccountNumber() ? 'ORDER.ACCOUNT_NUMBER' : 'ORDER_ID';
		}

		$query = Sale\TradingPlatform\OrderTable::getList([
			'filter' => [
				'=TRADING_PLATFORM_ID' => $platform->getId(),
				$search => $value,
			],
			'select' => $select,
			'order' => [ 'ID' => 'DESC' ],
			'limit' => 1000,
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row['EXTERNAL_ORDER_ID'];
		}

		return $result;
	}
}