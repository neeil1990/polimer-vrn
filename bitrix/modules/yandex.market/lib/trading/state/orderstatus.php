<?php

namespace Yandex\Market\Trading\State;

class OrderStatus
{
	public static function isChanged($serviceUniqueKey, $orderId, $status)
	{
		return (static::getValue($serviceUniqueKey, $orderId) !== $status);
	}

	public static function setValue($serviceUniqueKey, $orderId, $status)
	{
		Internals\StatusProxy::set($serviceUniqueKey, $orderId, $status);
	}

	public static function getValue($serviceUniqueKey, $orderId)
	{
		return Internals\StatusProxy::get($serviceUniqueKey, $orderId);
	}

	public static function commit($serviceUniqueKey, $orderId)
	{
		Internals\StatusProxy::commit($serviceUniqueKey, $orderId);
	}

	/** @deprecated */
	public static function releaseValue($serviceUniqueKey, $orderId)
	{
		// nothing
	}
}