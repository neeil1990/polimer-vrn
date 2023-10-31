<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class FileDownload extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function show()
	{
		$url = $this->getUrl();
		$setup = $this->getSetup();
		$options = $setup->wakeupService()->getOptions();

		list($contentType, $contents) = Market\Api\Partner\File\Facade::download($options, $url);

		$this->submitFile($contentType, $contents);
	}

	protected function getUrl()
	{
		$url = (string)$this->request->get('url');

		if ($url === '')
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_URL_NOT_DEFINED');
			throw new Main\SystemException($message);
		}

		return $url;
	}

	protected function getSetup()
	{
		$setupId = $this->getSetupId();
		$setup = Market\Trading\Setup\Model::loadById($setupId);

		if (!$setup->isActive())
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_SETUP_INACTIVE');
			throw new Main\SystemException($message);
		}

		return $setup;
	}

	protected function getSetupId()
	{
		$setupId = (int)$this->request->get('setup');

		if ($setupId <= 0)
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_SETUP_ID_NOT_DEFINED');
			throw new Main\SystemException($message);
		}

		return $setupId;
	}

	protected function submitFile($type, $contents)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		while (ob_get_level()) { ob_end_clean(); }
		header('Content-type: ' . $type);
		echo $contents;
		die();
	}
}