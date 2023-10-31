<?php
namespace Yandex\Market\Migration\V280;

use Yandex\Market\Export;

/** @noinspection PhpUnused */
class MoveChangesToWatcher
{
	public static function apply()
	{
		$setups = Export\Setup\Model::loadList([
			'filter' => [ '=AUTOUPDATE' => Export\Setup\Table::BOOLEAN_Y ],
		]);

		foreach ($setups as $setup)
		{
			$setup->updateListener();
		}

		return false;
	}
}