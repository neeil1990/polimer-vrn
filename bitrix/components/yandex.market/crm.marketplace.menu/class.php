<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

class CrmMarketplaceMenu extends \CBitrixComponent
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

		if (preg_match('#^(/yandexmarket(?:\d{1,3})?/marketplace(?:/|$))#', $page, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = '/yandexmarket/marketplace';
		}

		return $result;
	}

	public function executeComponent()
	{
		$this->fillMenu();
		$this->markActive();

		$this->includeComponentTemplate();
	}

	protected function fillMenu()
	{
		$items = $this->getItems();
		$items = $this->filterDeniedItems($items);
		$items = $this->filterHiddenItems($items);

		$this->arResult['MENU_ID'] = 'yamarket_marketplace';
		$this->arResult['MENU_ITEMS'] = $this->compileMenu($items);
	}

	protected function getItems()
	{
		return [
			'admin' => [],
			'documents' => [
				'DEFAULT' => true,
			],
			'shipments' => [
				'HIDDEN' => (Main\Config\Option::get('yandex.market', 'menu_logistic', 'N') !== 'Y'),
			],
			'event' => [],
			'help' => [
				'URL' => 'https://yandex.ru/support/marketplace-module-1c-bitrix/',
			],
		];
	}

	protected function filterDeniedItems(array $items)
	{
		$accessLevel = (string)\CMain::GetGroupRight('yandex.market');

		foreach ($items as $key => $data)
		{
			$required = isset($data['rights']) ? $data['rights'] : 'PT';

			if ($accessLevel[0] < $required[0])
			{
				$isMatchModuleRights = false;
			}
			else if ($accessLevel[0] > $required[0])
			{
				$isMatchModuleRights = true;
			}
			else
			{
				$isMatchModuleRights = ($accessLevel === $required);
			}

			if (!$isMatchModuleRights)
			{
				unset($items[$key]);
			}
		}

		return $items;
	}

	protected function filterHiddenItems(array $items)
	{
		foreach ($items as $key => $data)
		{
			if (!empty($data['HIDDEN']))
			{
				unset($items[$key]);
			}
		}

		return $items;
	}

	protected function compileMenu(array $items)
	{
		$result = [];

		foreach ($items as $key => $data)
		{
			$langKey = 'YANDEX_MARKET_CRM_MARKETPLACE_MENU_' . mb_strtoupper($key);

			$result[] = $data + [
				'ID' => 'yamarket_marketplace_' . $key,
				'TEXT' => Loc::getMessage($langKey) ?: $key,
				'URL' => $this->makeMenuUrl($key),
				'IS_ACTIVE' => false,
			];
		}

		return $result;
	}

	protected function makeMenuUrl($key)
	{
		global $APPLICATION;

		if ($this->arParams['SEF_MODE'])
		{
			$result = rtrim($this->arParams['SEF_FOLDER'], '/') . '/';

			if ((string)$key !== '')
			{
				$result .= $key . '/';
			}
		}
		else
		{
			$result = $APPLICATION->GetCurPageParam(
				http_build_query([ 'route' => $key ]),
				[ 'route' ],
				false
			);
		}

		return $result;
	}

	protected function markActive()
	{
		if (!$this->markActiveByUrl())
		{
			$this->markActiveByDefault();
		}
	}

	protected function markActiveByUrl()
	{
		$url = $this->request->getRequestUri();
		$result = false;

		foreach ($this->arResult['MENU_ITEMS'] as &$item)
		{
			if (mb_strpos($url, $item['URL']) === 0)
			{
				$item['IS_ACTIVE'] = true;
				$result = true;
				break;
			}
		}
		unset($item);

		return $result;
	}

	protected function markActiveByDefault()
	{
		foreach ($this->arResult['MENU_ITEMS'] as &$item)
		{
			if (!empty($item['DEFAULT']))
			{
				$item['IS_ACTIVE'] = true;
				break;
			}
		}
		unset($item);
	}
}