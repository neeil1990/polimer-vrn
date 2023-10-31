<?php

namespace Yandex\Market\Trading\Entity\Sale\Internals;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale') || !class_exists(Sale\TradingPlatform\Platform::class)) { return; }

class Platform extends Sale\TradingPlatform\Platform
{
	protected static $instanceCode;

	/**
	 * @deprecated
	 * @param string|null $code
	 *
	 * @return Sale\TradingPlatform\Platform|null
	 */
	public static function getInstance($code = null)
	{
		if ($code !== null)
		{
			return static::getInstanceCompatible($code);
		}

		if (static::$instanceCode === null)
		{
			static::$instanceCode = static::resolveInstanceCode();
		}

		return static::$instanceCode !== false
			? static::getInstanceCompatible(static::$instanceCode)
			: null;
	}

	protected static function getInstanceCompatible($code)
	{
		return \method_exists(static::class, 'getInstanceByCode')
			? static::getInstanceByCode($code)
			: parent::getInstance($code); // old bitrix behavior
	}

	protected static function resolveInstanceCode()
	{
		$result = false;

		$query = Sale\TradingPlatformTable::getList([
			'select' => [
				'CODE',
			],
			'filter' => [
				'=CLASS' => '\\' . static::class,
			],
			'order' => [
				'ACTIVE' => 'desc',
				'ID' => 'asc',
			],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = $row['CODE'];
		}

		return $result;
	}

	public function installExtended(array $data = [])
	{
		$fields = $this->prepareRecordData($data);

		return $this->addPlatformRecord($fields);
	}

	public function install()
	{
		return $this->installExtended()->getId();
	}

	public function updateExtended(array $data)
	{
		$fields = $this->prepareRecordData($data);

		return $this->updatePlatformRecord($fields);
	}

	public function migrate($newCode)
	{
		$updateResult = $this->updatePlatformRecord([
			'CODE' => $newCode,
		]);

		if ($updateResult->isSuccess())
		{
			$this->code = $newCode;
		}

		return $updateResult;
	}

	protected function addPlatformRecord(array $fields = [])
	{
		$defaults = [
			'CODE' => $this->getCode(),
			'CLASS' => '\\' . static::class,
			'ACTIVE' => 'N',
			'SETTINGS' => '',
		];
		$fields = $defaults + $fields;

		$addResult = Sale\TradingPlatformTable::add($fields);

		if ($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
			$this->isInstalled = true;
		}

		return $addResult;
	}

	protected function updatePlatformRecord(array $fields)
	{
		return Sale\TradingPlatformTable::update($this->id, $fields);
	}

	protected function prepareRecordData(array $data)
	{
		return array_intersect_key($data, [
			'NAME' => true,
			'DESCRIPTION' => true,
		]);
	}
}