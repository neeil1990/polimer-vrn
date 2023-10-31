<?php

namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main;
use Yandex\Market;

class SetupStatus
{
	const EXPORT_NOT_FOUND = 'NOT_FOUND';
	const EXPORT_FAIL = 'FAIL';
	const EXPORT_PROGRESS = 'PROGRESS';
	const EXPORT_READY = 'READY';

	public static function getExportState(Market\Export\Setup\Model $setup)
	{
		$setupId = $setup->getId();

		if (Market\Export\Run\Admin::hasProgress($setupId))
		{
			$result = Market\Export\Run\Admin::isProgressExpired($setupId)
				? static::EXPORT_FAIL
				: static::EXPORT_PROGRESS;
		}
		else if (static::existsExportTempFile($setup))
		{
			$result = static::EXPORT_FAIL;
		}
		else if (!$setup->isFileReady())
		{
			$result = static::EXPORT_NOT_FOUND;
		}
		else
		{
			$result = static::EXPORT_READY;
		}

		return $result;
	}

	protected static function existsExportTempFile(Market\Export\Setup\Model $setup)
	{
		$path = $setup->getFileAbsolutePath() . '.tmp';
		$file = new Main\IO\File($path);

		return $file->isExists();
	}
}