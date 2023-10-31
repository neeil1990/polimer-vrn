<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class CrmTradingRouter extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams['SEF_MODE'] = !isset($arParams['SEF_MODE']) || $arParams['SEF_MODE'] === 'Y';
		$arParams['SEF_FOLDER'] = isset($arParams['SEF_FOLDER']) ? trim($arParams['SEF_FOLDER']) : '';

		if ($arParams['SEF_MODE'] && $arParams['SEF_FOLDER'] === '')
		{
			$arParams['SEF_FOLDER'] = $this->resolveSefFolder();
		}

		return $arParams;
	}

	protected function resolveSefFolder()
	{
		$page = $this->request->getRequestedPage();

		if (preg_match('#^(/yandexmarket(?:\d{1,3})?/[a-z]+(?:/|$))#', $page, $matches))
		{
			$result = rtrim($matches[1], '/');
		}
		else
		{
			$result = '/yandexmarket/marketplace';
		}

		return $result;
	}

	public function executeComponent()
	{
		try
		{
			$this->loadModule();
			$this->loadAdminLib();

			$page = $this->isSefMode() ? $this->getSefPage() : $this->getRequestPage();

			$this->includeComponentTemplate($page);
		}
		catch (Main\SystemException $exception)
		{
			ShowError($exception->getMessage());
		}
	}

	protected function loadModule()
	{
		if (!Main\Loader::includeModule('yandex.market'))
		{
			$message = Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_MODULE_REQUIRED');
			throw new Main\SystemException($message);
		}
	}

	protected function loadAdminLib()
	{
		if (!defined('PUBLIC_MODE')) { define('PUBLIC_MODE', 1); }

		require_once Main\IO\Path::convertRelativeToAbsolute(BX_ROOT . '/modules/main/interface/admin_lib.php');
	}

	protected function isSefMode()
	{
		return $this->arParams['SEF_MODE'];
	}

	protected function getSefPage()
	{
		$variables = [];
		$defaultTemplates = $this->getDefaultTemplates();

		$engine = new \CComponentEngine($this);
		$urlTemplates = \CComponentEngine::makeComponentUrlTemplates($defaultTemplates, $this->arParams['SEF_URL_TEMPLATES']);

		$componentPage = $engine->guessComponentPath(
			$this->arParams['SEF_FOLDER'],
			$urlTemplates,
			$variables
		);

		if (!$componentPage)
		{
			$componentPage = $this->getDefaultPage();
		}

		return $componentPage;
	}

	protected function getRequestPage()
	{
		$requested = $this->request->get('route');
		$templates = $this->getDefaultTemplates();

		return isset($templates[$requested]) ? $requested : $this->getDefaultPage();
	}

	protected function getDefaultTemplates()
	{
		return [
			'admin' => 'admin/',
			'documents' => 'documents/',
			'shipments' => 'shipments/',
			'event' => 'event/',
		];
	}

	protected function getDefaultPage()
	{
		return 'documents';
	}
}