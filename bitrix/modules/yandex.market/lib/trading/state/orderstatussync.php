<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class OrderStatusSync extends Internals\AgentSkeleton
{
	protected static $expireDate;

	public static function getDefaultParams()
	{
		return [
			'interval' => static::getPeriod('restart', 86400),
		];
	}

	public static function start($setupId)
	{
		static::register([
			'method' => 'sync',
			'arguments' => [ $setupId ],
			'interval' => static::getPeriod('step', static::PERIOD_STEP_DEFAULT),
		]);
	}

	public static function sync($setupId, $offset = null, $errorCount = 0)
	{
		return static::wrapAction(
			[static::class, 'syncBody'],
			[ $setupId, $offset ],
			$errorCount
		);
	}

	protected static function syncBody($setupId, $offset = null)
	{
		$offset = static::sanitizeOffset($setupId, $offset);
		$dates = static::getSyncDates($offset['start'], $offset['finish']);
		$filter = static::makeDateFilter($dates, $offset['date']);

		if ($filter === null) { return false; }

		$setup = static::getSetup($setupId);
		$service = $setup->wakeupService();
		$orderCollection = static::loadOrderCollection($service, $filter, $offset['page']);
		$pager = $orderCollection->getPager();
		$hasNext = ($pager !== null && $pager->hasNext());
		$orders = static::mapOrderCollection($orderCollection);
		$orders = static::applyOffset($orders, $offset['page']);
		$accountNumberMap = static::getAccountNumberMap($orders, $setup);

		foreach ($orders as $orderId => $order)
		{
			++$offset['page'];

			if (!isset($accountNumberMap[$orderId])) { continue; }

			if (!static::isOrderCompleted($service, $order))
			{
				$offset['unfinished'] = static::oldestUnfinishedOffset($order->getCreationDate(), $offset['unfinished']);
			}

			if (!static::needProcess($service, $order)) { continue; }

			if (static::isTimeExpired())
			{
				$hasNext = true;
				break;
			}

			if (static::emulateStatus($setup, $order, $accountNumberMap[$orderId]))
			{
				static::commit($service, $order);
			}
		}

		if (!$hasNext && count($dates) > $offset['date'] + 1)
		{
			$hasNext = true;

			$offset['date'] += 1;
			$offset['page'] = 0;
		}

		if (!$hasNext)
		{
			static::commitOldestUnfinished($setupId, $offset['unfinished'], $offset['start']);
		}

		return $hasNext ? [ $setupId, $offset ] : false;
	}

	protected static function sanitizeOffset($setupId, $offset = null)
	{
		$now = (new Main\Type\Date());
		$defaults = [
			'start' => $now->format('Y-m-d'),
			'finish' => static::getOldestUnfinished($setupId),
			'unfinished' => null,
			'date' => 0,
			'page' => 0,
		];

		if (is_array($offset))
		{
			$result = $offset + $defaults;
		}
		else if ($offset !== null)
		{
			$result = $defaults;
			$result['page'] = $offset;
		}
		else
		{
			$result = $defaults;
		}

		return $result;
	}

	protected static function getSyncDates($startDateString = null, $finishDateString = null)
	{
		$days = static::getSyncDaysLimit();
		$step = static::getSyncDaysStep();
		$count = ceil($days / $step);
		$result = [];

		$loopDate = $startDateString !== null ? new Main\Type\Date($startDateString, 'Y-m-d') : new Main\Type\Date();
		$loopDate->add('P1D'); // fix query over date limit with local timezone

		for ($i = 1; $i <= $count; $i++)
		{
			$loopDate->add(sprintf('-P%sD', $step));

			if ($finishDateString !== null && $finishDateString > $loopDate->format('Y-m-d'))
			{
				$result[] = new Main\Type\Date($finishDateString, 'Y-m-d');
				break;
			}

			$result[] = clone $loopDate;
		}

		return $result;
	}

	protected static function getSyncDaysStep()
	{
		$name = static::optionName('days_step');
		$option = (int)Market\Config::getOption($name, 30);

		return max(1, $option);
	}

	protected static function getSyncDaysLimit()
	{
		$name = static::optionName('days_limit');
		$option = (int)Market\Config::getOption($name, 60);

		return max(1, $option);
	}

	protected static function getOptionPrefix()
	{
		return 'trading_status_sync';
	}

	protected static function getPageSize()
	{
		$name = static::optionName('page_size');
		$option = (int)Market\Config::getOption($name, 50);

		return max(1, min(50, $option));
	}

	protected static function makeDateFilter(array $dates, $offset = 0)
	{
		if (!isset($dates[$offset])) { return null; }

		$result = [
			'fromDate' => $dates[$offset],
		];

		if ($offset > 0)
		{
			$result['toDate'] = $dates[$offset - 1];
		}

		return $result;
	}

	protected static function isOrderCompleted(TradingService\Reference\Provider $service, Market\Api\Model\Order $order)
	{
		$orderStatus = $order->getStatus();
		$matched = null;
		$result = false;

		foreach ($service->getStatus()->getProcessOrder() as $loopStatus => $index)
		{
			if ($orderStatus === $loopStatus)
			{
				$matched = $index;
				$result = true;
			}
			else if ($matched !== null && $matched < $index)
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	protected static function oldestUnfinishedOffset(Main\Type\Date $date, $current = null)
	{
		$dateString = $date->format('Y-m-d');

		return ($current === null || $dateString < $current ? $dateString : $current);
	}

	protected static function commitOldestUnfinished($setupId, $unfinished = null, $start = null)
	{
		$name = static::optionName('unfinished_' . $setupId);
		$value = $unfinished !== null ? $unfinished : $start;

		Market\State::set($name, $value);
	}

	protected static function getOldestUnfinished($setupId)
	{
		$name = static::optionName('unfinished_' . $setupId);
		$stored = (string)Market\State::get($name);

		if ($stored === '') { return null; }

		return $stored;
	}

	/**
	 * @param TradingService\Reference\Provider $service
	 * @param int $offset
	 *
	 * @return Market\Api\Model\OrderCollection
	 * @throws Main\SystemException
	 */
	protected static function loadOrderCollection(TradingService\Reference\Provider $service, array $filter = [], $offset = 0)
	{
		/** @var Market\Api\Reference\HasOauthConfiguration $options */
		$options = $service->getOptions();
		$pageSize = static::getPageSize();
		$parameters = [
			'page' => floor($offset / $pageSize) + 1,
			'pageSize' => $pageSize,
		];
		$parameters += $filter;

		$orderFacade = $service->getModelFactory()->getOrderFacadeClassName();

		return $orderFacade::loadList($options, $parameters);
	}

	protected static function mapOrderCollection(Market\Api\Model\OrderCollection $orderCollection)
	{
		$result = [];

		/** @var Market\Api\Model\Order $order */
		foreach ($orderCollection as $order)
		{
			$result[$order->getId()] = $order;
		}

		return $result;
	}

	protected static function applyOffset(array $orders, $offset = 0)
	{
		$pageOffset = $offset % static::getPageSize();

		if ($pageOffset === 0) { return $orders; }

		return array_slice($orders, $pageOffset, null, true);
	}

	protected static function getAccountNumberMap(array $orders, Market\Trading\Setup\Model $setup)
	{
		return $setup->getEnvironment()->getOrderRegistry()->searchList(
			array_keys($orders),
			$setup->getPlatform(),
			false
		);
	}

	protected static function isChanged(TradingService\Reference\Provider $service, Market\Api\Model\Order $order)
	{
		$stored = OrderStatus::getValue($service->getUniqueKey(), $order->getId());
		$compare = static::compareOrderStoredStatus($service, $order, $stored);

		return (bool)$compare;
	}

	protected static function needProcess(TradingService\Reference\Provider $service, Market\Api\Model\Order $order)
	{
		$stored = Market\Trading\State\OrderData::getValue($service->getUniqueKey(), $order->getId(), 'INCOMING_STATUS');
		$compare = static::compareOrderStoredStatus($service, $order, $stored);

		return (bool)$compare;
	}

	protected static function compareOrderStoredStatus(TradingService\Reference\Provider $service, Market\Api\Model\Order $order, $stored)
	{
		if ($stored === null && static::isExpired($order)) { return null; }

		list($status, $substatus) = explode(':', (string)$stored, 2);

		return static::compareOrderStatus($service, $order, $status, $substatus);
	}

	protected static function compareOrderStatus(TradingService\Reference\Provider $service, Market\Api\Model\Order $order, $status, $substatus = null)
	{
		$statusService = $service->getStatus();
		$storedOrder = $statusService->getStatusOrder($status);
		$currentStatus = $order->getStatus();
		$currentOrder = $statusService->getStatusOrder($currentStatus);

		if ($currentOrder === null)
		{
			$result = null;
		}
		else if ($status !== $currentStatus && $currentOrder >= $storedOrder)
		{
			$result = true;
		}
		else if ($status === $currentStatus && $statusService->hasSubstatus($status))
		{
			$storedSubstatusOrder = $statusService->getSubStatusOrder($substatus);
			$currentSubstatus = $order->getSubStatus();
			$currentSubstatusOrder = $statusService->getSubStatusOrder($currentSubstatus);

			$result = (
				$currentSubstatusOrder !== null
				&& $currentSubstatus !== $substatus
				&& $currentSubstatusOrder >= $storedSubstatusOrder
			 );
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected static function isExpired(Market\Api\Model\Order $order)
	{
		$expireDate = static::getExpireDate();
		$createDate = $order->getCreationDate();

		return Market\Data\DateTime::compare($createDate, $expireDate) === -1;
	}

	protected static function getExpireDate()
	{
		if (static::$expireDate === null)
		{
			$expireDays = static::getExpireDays();
			$expireDate = new Main\Type\DateTime();
			$expireDate->add(sprintf('-P%sD', $expireDays));

			static::$expireDate = $expireDate;
		}

		return static::$expireDate;
	}

	protected static function getExpireDays()
	{
		return Internals\DataCleaner::getExpireDays('status');
	}

	protected static function commit(TradingService\Reference\Provider $service, Market\Api\Model\Order $order)
	{
		$statusEncoded = implode(':', [
			$order->getStatus(),
			$order->getSubStatus()
		]);

		if (static::needProcess($service, $order))
		{
			OrderData::setValue($service->getUniqueKey(), $order->getId(), 'INCOMING_STATUS', $statusEncoded);
		}

		OrderStatus::commit($service->getUniqueKey(), $order->getId());
	}

	protected static function emulateStatus(Market\Trading\Setup\Model $setup, Market\Api\Model\Order $order, $accountNumber)
	{
		$logger = null;
		$audit = null;

		try
		{
			$environment = $setup->getEnvironment();
			$service = $setup->wakeupService();
			$logger = $service->getLogger();
			$server = Main\Context::getCurrent()->getServer();
			$request = static::makeRequestFromOrder($server, $order);

			$action = $service->getRouter()->getHttpAction('order/status', $environment, $request, $server);
			$audit = $action->getAudit();

			$action->process();

			$result = true;
		}
		catch (Main\SystemException $exception)
		{
			if ($logger === null) { throw $exception; }

			$logger->error($exception, array_filter([
				'AUDIT' => $audit,
				'ENTITY_TYPE' => Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
				'ENTITY_ID' => $accountNumber,
			]));

			$result = false;
		}

		return $result;
	}

	protected static function makeRequestFromOrder(Main\Server $server, Market\Api\Model\Order $order)
	{
		return new Main\HttpRequest(
			$server,
			[], // query string
			[
				'order' => $order->getFields(),
				'emulated' => true,
			], // post
			[], // files
			[] // cookies
		);
	}
}