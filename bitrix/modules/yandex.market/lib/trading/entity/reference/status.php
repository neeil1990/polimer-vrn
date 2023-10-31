<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Status
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param string $status
	 * @param string $version
	 *
	 * @return string
	 */
	public function getTitle($status, $version = '')
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getTitle');
	}

	/**
	 * @param string[] $variants
	 *
	 * @return string[]
	 */
	public function getEnum($variants)
	{
		$result = [];

		foreach ($variants as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => '[' . $variant . '] ' . $this->getTitle($variant),
			];
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	public function getVariants()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getVariants');
	}

	public function isStandalone($status)
	{
		return true;
	}

	public function getGroup($status)
	{
		return $status;
	}

	final public function getMeaningful($status)
	{
		$map = $this->getMeaningfulMap();
		$result = null;

		foreach ($map as $meaningful => $matched)
		{
			$isMatched = is_array($matched)
				? in_array($status, $matched, true)
				: $matched === $status;

			if ($isMatched)
			{
				$result = $meaningful;
				break;
			}
		}

		return $result;
	}

	/**
	 * @return string[]|string[][]
	 */
	public function getMeaningfulMap()
	{
		return [];
	}

	/**
	 * @return string[]|string[][]
	 */
	public function getCancelReasonMeaningfulMap()
	{
		return [];
	}
}