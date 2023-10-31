<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

class TradingPrintForm extends \CBitrixComponent
{
	protected $setup;
	protected $document;

	public function executeComponent()
	{
		try
		{
			$this->loadModules();
			$this->setTitle();

			if ($this->getRequestAction() === 'print')
			{
				$this->validatePrintRequest();

				$templatePage = $this->printAction();
			}
			else
			{
				$templatePage = $this->formAction();
			}

			$templatePage = $this->formatTemplateName($templatePage);
		}
		catch (Main\SystemException $exception)
		{
			$this->disableAutoPrint();

			$this->arResult['ERROR'] = $exception->getMessage();
			$templatePage = 'exception';
		}

		$this->includeComponentTemplate($templatePage);
	}

	protected function setTitle()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle($this->getDocument()->getTitle());
	}

	protected function formatTemplateName($templatePage)
	{
		return preg_replace_callback('/[A-Z]+/', static function($matches) {
			return '-' . Market\Data\TextString::toLower($matches[0]);
		}, $templatePage);
	}

	protected function validatePrintRequest()
	{
		if (!$this->request->isPost())
		{
			$message = $this->getLang('PRINT_ONLY_POST');
			throw new Main\SystemException($message);
		}

		if (!check_bitrix_sessid())
		{
			$message = $this->getLang('SESSION_EXPIRED');
			throw new Main\SystemException($message);
		}

		if ($this->request->getPost('entityType') !== $this->getEntityType())
		{
			$message = $this->getLang('PRINT_ENTITY_TYPE_NOT_MATCH');
			throw new Main\SystemException($message);
		}
	}

	protected function printAction()
	{
		$entitySelect = $this->getRequestedEntities();
		$settings = $this->getRequestedSettings();
		$items = $this->loadPrintItems($entitySelect);

		list($type, $contents) = $this->render($items, $settings);
		$this->fillContents($contents, $type);

		return 'print';
	}

	protected function disableAutoPrint()
	{
		global $APPLICATION;

		$APPLICATION->SetPageProperty('YAMARKET_PAGE_PRINT', 'N');
	}

	protected function getRequestedSettings()
	{
		$requestSettings = (array)$this->request->getPost('settings');
		$result = [];

		foreach ($this->getSettings() as $name => $field)
		{
			if (isset($requestSettings[$name]) && !Market\Utils\Value::isEmpty($requestSettings[$name]))
			{
				$value = $requestSettings[$name];
				$this->checkSettingValue($field, $value);

				if ($field['PERSISTENT'] === 'Y' && $value !== $field['VALUE'] && is_scalar($value))
				{
					$this->saveSettingPersistentValue($name, $value);
				}

				$result[$name] = $value;
			}
			else if ($field['MANDATORY'] === 'Y')
			{
				$message = $this->getLang('REQUIRED_SETTING', [ '#NAME#' => $field['NAME'] ]);
				throw new Main\SystemException($message);
			}
		}

		return $result;
	}

	protected function loadPrintItems($entitySelect)
	{
		$document = $this->getDocument();

		if ($document instanceof TradingService\Reference\Document\HasLoadItems)
		{
			$this->getSetup()->wakeupService();

			$items = $document->loadItems($entitySelect);
		}
		else if ($this->needLoad())
		{
			if (!isset($entitySelect['ORDER']))
			{
				throw new Main\SystemException('only ORDER loading support');
			}

			$loadResult = $this->loadSelectedOrders($entitySelect['ORDER']);
			$items = $this->makeItems($loadResult['ORDERS'], 'ORDER');
		}
		else
		{
			$items = [];
		}

		return $items;
	}

	protected function render(array $items, array $settings = [])
	{
		$document = $this->getDocument();

		if ($document instanceof TradingService\Reference\Document\HasRenderFile && $document->canRenderFile($items, $settings))
		{
			list($type, $content) = $document->renderFile($items, $settings);
		}
		else
		{
			$type = 'text/html';
			$content = $this->getDocument()->render($items, $settings);
		}

		return [$type, $content];
	}

	protected function fillContents($contents, $type = 'text/html')
	{
		$this->arResult['CONTENT_TYPE'] = $type;
		$this->arResult['CONTENT_RAW'] = $contents;
	}

	protected function getRequestedEntities()
	{
		$entityIds = (array)$this->request->getPost('entity');
		$chain = $this->getItemChain();
		$chainLength = count($chain);
		$result = array_fill_keys($chain, []);

		foreach ($entityIds as $entityId)
		{
			$entityIdParts = explode(':', $entityId);

			if ($chainLength === count($entityIdParts))
			{
				$keyIndex = 0;

				foreach ($chain as $key)
				{
					$entityIdPart = $entityIdParts[$keyIndex];

					if (!in_array($entityIdPart, $result[$key], true))
					{
						$result[$key][] = $entityIdPart;
					}

					++$keyIndex;
				}
			}
		}

		return $result;
	}

	protected function formAction()
	{
		$isLoadMoreRequest = $this->isLoadMoreRequest();

		$this->fillCommon();

		if ($this->needLoad())
		{
			$selectedOrderIds = $this->getSelectedOrderIds();

			if (!$isLoadMoreRequest)
			{
				$document = $this->getDocument();

				if ($document instanceof TradingService\Reference\Document\HasLoadForm)
				{
					$items = $document->loadForm([ 'id' => $selectedOrderIds ]);
					$this->setItems('ITEMS', $items);
				}
				else if ($this->getSourceType() === $this->getEntityType())
				{
					$loadResult = $this->emulateOrders($selectedOrderIds);
					$this->fillItems('ITEMS', $loadResult);
				}
				else
				{
					$loadResult = $this->loadSelectedOrders($selectedOrderIds);
					$this->fillItems('ITEMS', $loadResult);
				}
			}

			if ($this->useAdditionalItems())
			{
				$page = $isLoadMoreRequest ? (int)$this->request->get('page') : null;

				$loadResult = $this->loadAdditionalOrders($page);
				$loadResult = $this->filterSelectedOrders($loadResult, $selectedOrderIds);

				$this->fillItems('ADDITIONAL_ITEMS', $loadResult, $page);
			}
		}

		$this->fillSettings();

		return $this->getEntityType();
	}

	protected function loadModules()
	{
		$requiredModules = $this->getRequiredModules();

		foreach ($requiredModules as $requiredModule)
		{
			if (!Main\Loader::includeModule($requiredModule))
			{
				$message = $this->getLang('MODULE_NOT_INSTALLED', [ '#MODULE_ID#' => $requiredModule ]);

				throw new Main\SystemException($message);
			}
		}
	}

	protected function getRequiredModules()
	{
		return [
			'yandex.market',
		];
	}

	protected function getSetup()
	{
		if ($this->setup === null)
		{
			$this->setup = $this->loadSetup();
		}

		return $this->setup;
	}

	protected function loadSetup()
	{
		$id = (int)$this->getParameter('SETUP_ID');

		return Market\Trading\Setup\Model::loadById($id);
	}

	protected function getParameter($key)
	{
		$result = $this->arParams[$key];

		if (Market\Utils\Value::isEmpty($result))
		{
			$message = $this->getLang('PARAMETER_' . $key . '_REQUIRED');
			throw new Main\ArgumentException($message);
		}

		return $result;
	}

	protected function useAdditionalItems()
	{
		return (
			$this->arParams['USE_ADDITIONAL']
			&& $this->getEntityType() === Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER
		);
	}

	protected function getSourceType()
	{
		return $this->getDocument()->getSourceType();
	}

	protected function getEntityType()
	{
		return $this->getDocument()->getEntityType();
	}

	protected function needLoad()
	{
		return $this->getEntityType() !== Market\Trading\Entity\Registry::ENTITY_TYPE_NONE;
	}

	protected function getDocument()
	{
		if ($this->document === null)
		{
			$this->document = $this->loadDocument();
		}

		return $this->document;
	}

	protected function loadDocument()
	{
		$setup = $this->getSetup();
		$type = $this->getParameter('TYPE');

		return $setup->wakeupService()->getPrinter()->getDocument($type);
	}

	protected function getLang($code, $replace = null, $language = null)
	{
		return Main\Localization\Loc::getMessage('YANDEX_MARKET_TRADING_PRINT_FORM_' . $code, $replace, $language);
	}

	protected function getRequestAction()
	{
		return $this->request->get('action');
	}

	protected function isLoadMoreRequest()
	{
		return $this->getRequestAction() === 'loadMore';
	}

	protected function loadSelectedOrders($selectedIds)
	{
		return $this->requestOrders([
			'id' => $selectedIds,
			'useCache' => true,
		]);
	}

	protected function getSelectedOrderIds()
	{
		return (array)$this->getParameter('EXTERNAL_ID');
	}

	protected function loadAdditionalOrders($page = null)
	{
		$fetchParameters = [
			'printReady' => true,
			'useCache' => true,
		];

		if ($page !== null)
		{
			$fetchParameters['page'] = (int)$page;
		}

		return $this->requestOrders($fetchParameters);
	}

	protected function filterSelectedOrders($loadResult, $selectedIds)
	{
		$selectedMap = array_flip($selectedIds);

		$loadResult['ORDERS'] = array_filter($loadResult['ORDERS'], static function($order) use ($selectedMap) {
			return !isset($selectedMap[$order['ID']]);
		});

		return $loadResult;
	}

	protected function emulateOrders($orderIds)
	{
		return [
			'ORDERS' => array_map(static function($orderId) { return [ 'ID' => $orderId ]; }, $orderIds),
			'NEXT_PAGE' => null,
		];
	}

	protected function requestOrders(array $parameters = [])
	{
		$procedure = new Market\Trading\Procedure\Runner(Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER, null);
		$setup = $this->getSetup();
		$logger = $setup->wakeupService()->getLogger();
		$parameters += $this->getEnvironmentFetchParameters();

		$this->configureLogger($logger);

		$response = $procedure->run($setup, 'admin/list', $parameters);

		return [
			'ORDERS' => $response->getField('orders'),
			'NEXT_PAGE' => $response->getField('nextPage'),
		];
	}

	protected function configureLogger($logger)
	{
		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$logger->setLevel(Market\Logger\Level::ERROR);
		}
	}

	protected function getEnvironmentFetchParameters()
	{
		global $USER;

		return [
			'userId' => $USER->GetID(),
			'checkAccess' => (bool)$this->arParams['CHECK_ACCESS'],
		];
	}

	protected function fillCommon()
	{
		$service = $this->getSetup()->getService();
		$serviceInfo = $service->getInfo();
		$document = $this->getDocument();

		$this->arResult['ENTITY_TYPE'] = $this->getEntityType();
		$this->arResult['ITEMS'] = [];
		$this->arResult['LOAD_MORE'] = $this->isLoadMoreRequest();
		$this->arResult['SERVICE_NAME'] = $serviceInfo->getTitle();
		$this->arResult['SERVICE_NAME_SHORT'] = $serviceInfo->getTitle('SHORT');
		$this->arResult['DOCUMENT_TITLE'] = $document->getTitle();
		$this->arResult['DOCUMENT_DESCRIPTION'] = $document->getMessage('DESCRIPTION');
	}

	protected function fillItems($resultKey, $loadResult, $page = null)
	{
		$baseKey = str_replace('ITEMS', '', $resultKey);
		$currentPageKey = $baseKey . 'PAGE';
		$nextPageKey = $baseKey . 'NEXT_PAGE';

		$this->arResult[$resultKey] = $this->makeItems($loadResult['ORDERS']);
		$this->arResult[$currentPageKey] = $page;

		if ($loadResult['NEXT_PAGE'])
		{
			$this->arResult[$nextPageKey] = $loadResult['NEXT_PAGE'];
			$this->arResult[$nextPageKey . '_URL'] = $this->getNextPageUrl($loadResult['NEXT_PAGE']);
		}
	}

	protected function setItems($resultKey, $items, $page = null)
	{
		$baseKey = str_replace('ITEMS', '', $resultKey);
		$currentPageKey = $baseKey . 'PAGE';

		$this->arResult[$resultKey] = $items;
		$this->arResult[$currentPageKey] = $page;
	}

	protected function getNextPageUrl($nextPage)
	{
		global $APPLICATION;

		$query = [
			'page' => $nextPage,
			'action' => 'loadMore',
		];

		return $APPLICATION->GetCurPageParam(http_build_query($query), array_keys($query));
	}

	protected function makeItems($orders, $start = null)
	{
		$chain = $this->getItemChain();
		$chain = $this->applyItemChainStart($chain, $start);
		$result = [];

		foreach ($orders as $order)
		{
			$chainItems = $this->applyItemChain($order, $chain);

			foreach ($chainItems as $chainItem)
			{
				$result[] = $chainItem;
			}
		}

		return $result;
	}

	protected function getItemChain()
	{
		switch ($this->getSourceType())
		{
			case Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT:
				$result = $this->getShipmentItemChain();
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER:
				$result = $this->getOrderItemChain();
			break;

			default:
				throw new Main\SystemException('unknown source type');
		}

		return $result;
	}

	protected function applyItemChainStart(array $chain, $start)
	{
		if ($start === null) { return $chain; }

		$startPosition = array_search($start, $chain, true);

		if ($startPosition === false)
		{
			throw new Main\SystemException(sprintf(
				'cannot start from %s with entity type %s',
				$start,
				$this->getEntityType()
			));
		}

		array_splice($chain, 0, $startPosition);

		return $chain;
	}

	protected function getShipmentItemChain()
	{
		switch ($this->getEntityType())
		{
			case Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT:
				$result = [
					'LOGISTIC_SHIPMENT',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER:
				$result = [
					'LOGISTIC_SHIPMENT',
					'ORDER',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_BOX:
				$result = [
					'LOGISTIC_SHIPMENT',
					'ORDER',
					'SHIPMENT',
					'BOX',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_NONE:
				$result = [];
			break;

			default:
				throw new Main\SystemException('unknown entity type');
		}

		return $result;
	}

	protected function getOrderItemChain()
	{
		switch ($this->getEntityType())
		{
			case Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER:
				$result = [
					'ORDER',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_SHIPMENT:
				$result = [
					'ORDER',
					'SHIPMENT',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_BOX:
				$result = [
					'ORDER',
					'SHIPMENT',
					'BOX',
				];
			break;

			case Market\Trading\Entity\Registry::ENTITY_TYPE_NONE:
				$result = [];
			break;

			default:
				throw new Main\SystemException('unknown entity type');
			break;
		}

		return $result;
	}

	protected function applyItemChain($order, $chain)
	{
		$chainEntities = [ $order ];
		$previousKey = array_shift($chain);
		$result = [];

		foreach ($chain as $key)
		{
			$previousEntities = $chainEntities;
			$chainEntities = [];

			foreach ($previousEntities as $previousEntity)
			{
				if (!isset($previousEntity[$key]) || !is_array($previousEntity[$key])) { continue; }

				foreach ($previousEntity[$key] as $chainEntity)
				{
					$chainEntity['CHAIN'] = isset($previousEntity['CHAIN']) ? $previousEntity['CHAIN'] : [];
					$chainEntity['CHAIN'][$previousKey] = array_diff_key($previousEntity, [ 'CHAIN' => true, $key => true ]);

					$chainEntities[] = $chainEntity;
				}
			}

			$previousKey = $key;
		}

		foreach ($chainEntities as $chainEntity)
		{
			if (isset($chainEntity['CHAIN']))
			{
				$resultItem = array_diff_key($chainEntity, [ 'CHAIN' => true ]);
				$entityIdParts = [];

				foreach ($chainEntity['CHAIN'] as $previousKey => $previousEntity)
				{
					$entityIdParts[] = $previousEntity['ID'];

					$resultItem += $this->prefixChainEntity($previousEntity, $previousKey . '_');
				}

				$entityIdParts[] = $chainEntity['ID'];
				$resultItem['ENTITY_ID'] = implode(':', $entityIdParts);
			}
			else
			{
				$resultItem = $chainEntity;
				$resultItem['ENTITY_ID'] = $chainEntity['ID'];
			}

			$result[] = $resultItem;
		}

		return $result;
	}

	protected function prefixChainEntity($entity, $prefix)
	{
		$result = [];

		foreach ($entity as $key => $value)
		{
			$result[$prefix . $key] = $value;
		}

		return $result;
	}

	protected function fillSettings()
	{
		$this->arResult['SETTINGS'] = $this->getSettings();
	}

	protected function getSettings()
	{
		$settings = $this->getDocument()->getSettings();

		return $this->extendSettings($settings);
	}

	protected function extendSettings($settings)
	{
		foreach ($settings as $name => &$setting)
		{
			$setting += [
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => 'Y',
				'EDIT_FORM_LABEL' => $setting['NAME'],
				'FIELD_NAME' => 'settings[' . $name . ']',
				'PERSISTENT' => 'N',
				'SETTINGS' => [],
			];

			if ($setting['PERSISTENT'] === 'Y')
			{
				$setting['VALUE'] = $this->getSettingPersistentValue($name);
			}

			if (!isset($setting['USER_TYPE']) && isset($setting['TYPE']))
			{
				$setting['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($setting['TYPE']);
			}
		}
		unset($setting);

		return $settings;
	}

	protected function getSettingPersistentValue($name)
	{
		global $APPLICATION;

		$cookieName = $this->getSettingPersistentName($name);
		$cookieValue = (string)$APPLICATION->get_cookie($cookieName);
		$result = null;

		if ($cookieValue !== '')
		{
			$result = $cookieValue;
		}

		return $result;
	}

	protected function saveSettingPersistentValue($name, $value)
	{
		global $APPLICATION;

		$cookieName = $this->getSettingPersistentName($name);

		$APPLICATION->set_cookie($cookieName, $value);
	}

	protected function getSettingPersistentName($name)
	{
		return
			'YAMARKET_PRINT_'
			. Market\Data\TextString::toUpper($this->arParams['TYPE'])
			. '_'
			. Market\Data\TextString::toUpper($name);
	}

	public function getSettingHtml($setting)
	{
		global $USER_FIELD_MANAGER;

		$html = $USER_FIELD_MANAGER->GetEditFormHTML(false, null, $setting);

		return $this->extractAdminInput($html);
	}

	protected function checkSettingValue($setting, $value)
	{
		if (!empty($setting['USER_TYPE']['CLASS_NAME']) && is_callable([$setting['USER_TYPE']['CLASS_NAME'], 'CheckFields']))
		{
			$userErrors = call_user_func(
				[$setting['USER_TYPE']['CLASS_NAME'], 'CheckFields'],
				$setting,
				$value
			);

			if (!empty($userErrors) && is_array($userErrors))
			{
				$userError = reset($userErrors);
				throw new Main\SystemException($userError['text']);
			}
		}
	}

	protected function extractAdminInput($html)
	{
		$result = $html;

		if (preg_match('/^<tr.*?>(?:<td.*?>.*?<\/td>)?<td.*?>(.*)<\/td><\/tr>$/s', $html, $match))
		{
			$result = $match[1];
		}

		return $result;
	}
}