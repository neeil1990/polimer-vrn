<?php
namespace Yandex\Market\Watcher\Agent;

use Yandex\Market\Utils\ArrayHelper;

class StateFacade
{
	public static function release($method, $setupType, $setupId)
	{
		$primary = [
			'METHOD' => $method,
			'SETUP_TYPE' => $setupType,
			'SETUP_ID' => $setupId,
		];

		$row = StateTable::getRow([
			'filter' => ArrayHelper::prefixKeys($primary, '='),
		]);

		if ($row)
		{
			StateTable::update($primary, [
				'STEP' => '',
				'OFFSET' => '',
			]);
		}
	}

	public static function drop($method, $setupType, $setupId)
	{
		StateTable::deleteBatch([
			'filter' => [
				'=METHOD' => $method,
				'=SETUP_TYPE' => $setupType,
				'=SETUP_ID' => $setupId,
			],
		]);
	}
}