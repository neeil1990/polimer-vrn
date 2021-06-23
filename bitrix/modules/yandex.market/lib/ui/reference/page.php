<?php

namespace Yandex\Market\Ui\Reference;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Page
{
	protected $request;
	protected $warnings = [];

	public function __construct(Main\HttpRequest $request = null)
	{
		$this->request = $request !== null ? $request : Main\Context::getCurrent()->getRequest();
	}

	public function checkSession()
	{
		if (!check_bitrix_sessid())
		{
			throw new Main\SystemException(Market\Config::getLang('SESSION_EXPIRED'));
		}
	}

	public function checkReadAccess()
	{
		$rights = $this->getReadRights();

		if (!$this->isAuthorized($rights))
		{
			throw new Main\AccessDeniedException(Market\Config::getLang('READ_ACCESS_DENIED'));
		}
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_READ;
	}

	public function checkWriteAccess()
	{
		$rights = $this->getWriteRights();

		if (!$this->isAuthorized($rights))
		{
			throw new Main\AccessDeniedException(Market\Config::getLang('WRITE_ACCESS_DENIED'));
		}
	}

	protected function getWriteRights()
	{
		return Market\Ui\Access::RIGHTS_WRITE;
	}

	public function loadModules()
	{
		$modules = $this->getRequiredModules();

		foreach ($modules as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException(Market\Config::getLang('REQUIRE_MODULE', [ '#MODULE#' => $module ]));
			}
		}
	}

	public function getRequiredModules()
	{
		return [];
	}

	public function isAuthorized($level)
	{
		return Market\Ui\Access::hasRights($level);
	}

	public function refreshPage()
	{
		global $APPLICATION;

		$url = $APPLICATION->GetCurPageParam('', [ 'action', 'sessid' ]);

		LocalRedirect($url);
	}

	public function addWarning($message)
	{
		$this->warnings[] = $message;
	}

	public function hasWarnings()
	{
		return !empty($this->warnings);
	}

	public function showWarnings()
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => implode('<br />', $this->warnings),
			'HTML' => true
		]);
	}
}