<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;

class Dictionary
{
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
	 * @return string|null
	 */
	public function getOrderItemXmlId(Market\Api\Model\Order\Item $item)
	{
		$itemId = $item->getId();

		return $itemId !== null
			? $this->getCommonPrefix() . $itemId
			: null;
	}

	protected function getCommonPrefix()
	{
		if ($this->commonPrefix === null)
		{
			$serviceCode = $this->provider->getCode();
			$serviceCode = Market\Data\TextString::toUpper($serviceCode);
			$serviceCode = str_replace(':', '_', $serviceCode);

			$this->commonPrefix = 'YAMARKET_' . $serviceCode . '_';
		}

		return $this->commonPrefix;
	}
}