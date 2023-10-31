<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;

class Dictionary
{
	const PREFIX_BASE = 'YAMARKET_';

	protected $provider;
	protected $commonPrefix;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function getErrorPrefix()
	{
		return $this->getCommonPrefix();
	}

	/**
	 * @param Market\Error\Base|Main\Error|string $error
	 *
	 * @return string
	 */
	public function getErrorCode($error)
	{
		$errorCode = is_string($error) ? $error : $error->getCode();

		return $this->getErrorPrefix() . $errorCode;
	}

	/**
	 * @param Market\Api\Model\Order\Item $item
	 *
	 * @return string
	 */
	public function getOrderItemXmlId(Market\Api\Model\Order\Item $item)
	{
		$prefix = $this->getCommonPrefix();
		$itemId = $item->getId();

		if ($itemId !== null) { return $prefix . $itemId; }

		$order = $item->getParent();
		$index = $item->getCollection()->getItemIndex($item);

		if ($order === null)
		{
			throw new Main\ArgumentException('item without order not supported');
		}

		return $prefix . $order->getId() . '_' . $index;
	}

	public function parseOrderItemXmlId($xmlId)
	{
		$prefix = $this->getCommonPrefix();

		if (Market\Data\TextString::getPosition($xmlId, $prefix) !== 0) { return null; }

		$prefixLength = Market\Data\TextString::getLength($prefix);
		$left = Market\Data\TextString::getSubstring($xmlId, $prefixLength);
		$left = preg_replace('/_R\d+$/', '', $left);

		if (preg_match('/_(\d{1,3})$/', $left, $matches))
		{
			$result = [ 'INDEX' => $matches[1] ];
		}
		else
		{
			$result = [ 'ID' => $left ];
		}

		return $result;
	}

	protected function getCommonPrefix()
	{
		if ($this->commonPrefix === null)
		{
			$serviceCode = $this->provider->getCode();
			$serviceCode = Market\Data\TextString::toUpper($serviceCode);
			$serviceCode = str_replace(':', '_', $serviceCode);

			$this->commonPrefix = static::PREFIX_BASE . $serviceCode . '_';
		}

		return $this->commonPrefix;
	}
}