<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class TradingOrderView extends \CBitrixComponent
{
	protected $setup;

	public function onPrepareComponentParams($params)
	{
		$params['CHECK_ACCESS'] = !isset($params['CHECK_ACCESS']) || $params['CHECK_ACCESS'];
		$params['EXTERNAL_ID'] = trim($params['EXTERNAL_ID']);
		$params['SETUP_ID'] = !empty($params['SETUP_ID']) ? (int)$params['SETUP_ID'] : null;
		$params['SERVICE_CODE'] = !empty($params['SERVICE_CODE']) ? trim($params['SERVICE_CODE']) : null;
		$params['BEHAVIOR_CODE'] = !empty($params['BEHAVIOR_CODE']) ? trim($params['BEHAVIOR_CODE']) : null;
		$params['SITE_ID'] = !empty($params['SITE_ID']) ? trim($params['SITE_ID']) : null;

		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return [
			'CHECK_ACCESS',
			'EXTERNAL_ID',
			'SETUP_ID',
			'SERVICE_CODE',
			'BEHAVIOR_CODE',
			'SITE_ID',
		];
	}

	public function executeComponent()
	{
		try
		{
			$this->loadModules();

			$orderExternalId = $this->getOrderExternalId();
			$orderNum = $this->getOrderNum($orderExternalId);
			$orderInternalId = $this->getOrderNum($orderExternalId, false);
			$response = $this->runAction($orderExternalId, $orderNum);

			$this->buildResult($response);
			$this->extendResult($orderExternalId, $orderNum, $orderInternalId);

			$templatePage = $this->resolveTemplatePage();
		}
		catch (Main\SystemException $exception)
		{
			$this->arResult['ERROR'] = $exception->getMessage();
			$templatePage = 'exception';
		}

		$this->includeComponentTemplate($templatePage);

		return isset($this->arResult['RETURN']) ? $this->arResult['RETURN'] : null;
	}

	protected function resolveTemplatePage()
	{
		$mode = isset($this->arParams['MODE']) ? $this->arParams['MODE'] : '';

		if ($mode === 'RELOAD')
		{
			$result = 'reload';
		}
		else
		{
			$result = '';
		}

		return $result;
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
		try
		{
			$result = $this->loadSetupById();
		}
		catch (Main\ArgumentException $exception)
		{
			$result = $this->loadSetupByService();
		}

		return $result;
	}

	protected function loadSetupById()
	{
		$setupId = $this->getParameterSetupId();

		return Market\Trading\Setup\Model::loadById($setupId);
	}

	protected function loadSetupByService()
	{
		$siteId = $this->getParameterSiteId();
		$serviceCode = $this->getParameterServiceCode();
		$behaviorCode = $this->getParameterBehaviorCode();

		return Market\Trading\Setup\Model::loadByServiceAndSite($serviceCode, $siteId, $behaviorCode);
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

	protected function getParameterSetupId()
	{
		return $this->getRequiredParameter('SETUP_ID');
	}

	protected function getParameterServiceCode()
	{
		return $this->getRequiredParameter('SERVICE_CODE');
	}

	protected function getParameterBehaviorCode()
	{
		return $this->getParameter('BEHAVIOR_CODE') ?: null;
	}

	protected function getParameterSiteId()
	{
		return $this->getRequiredParameter('SITE_ID');
	}

	protected function getRequiredParameter($key)
	{
		$result = $this->getParameter($key);

		if ($result === '')
		{
			$message = $this->getLang('PARAMETER_' . $key . '_REQUIRED');
			throw new Main\ArgumentException($message);
		}

		return $result;
	}

	protected function getParameter($key)
	{
		return isset($this->arParams[$key]) ? (string)$this->arParams[$key] : '';
	}

	protected function getLang($code, $replace = null, $language = null)
	{
		return Main\Localization\Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_VIEW_' . $code, $replace, $language);
	}

	protected function getOrderExternalId()
	{
		return (int)$this->getParameter('EXTERNAL_ID');
	}

	protected function getOrderNum($externalId, $useAccountNumber = null)
	{
		$platform = $this->getSetup()->getPlatform();
		$registry = $this->getSetup()->getEnvironment()->getOrderRegistry();
		$result = $registry->search($externalId, $platform, $useAccountNumber);

		if ($result === null)
		{
			$message = $this->getLang('ORDER_NOT_REGISTERED', [ '#EXTERNAL_ID#' => $externalId ]);
			throw new Main\SystemException($message);
		}

		return $result;
	}

	protected function runAction($externalId, $orderNum)
	{
		$setup = $this->getSetup();
		$procedure = new Market\Trading\Procedure\Runner(
			Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
			$orderNum
		);

		$parameters = [
			'id' => $externalId,
			'flushCache' => true,
			'useCache' => true,
		];
		$parameters += $this->getEnvironmentFetchParameters();

		return $procedure->run($setup, 'admin/view', $parameters);
	}

	protected function getEnvironmentFetchParameters()
	{
		global $USER;

		return [
			'userId' => $USER->GetID(),
			'checkAccess' => (bool)$this->arParams['CHECK_ACCESS'],
		];
	}

	protected function getBoxDimensions()
	{
		return [
			'WIDTH' => [
				'NAME' => $this->getLang('DIMENSION_WIDTH'),
				'UNIT' => Market\Data\Size::UNIT_CENTIMETER,
			],
			'HEIGHT' => [
				'NAME' => $this->getLang('DIMENSION_HEIGHT'),
				'UNIT' => Market\Data\Size::UNIT_CENTIMETER,
			],
			'DEPTH' => [
				'NAME' => $this->getLang('DIMENSION_DEPTH'),
				'UNIT' => Market\Data\Size::UNIT_CENTIMETER,
			],
			'WEIGHT' => [
				'NAME' => $this->getLang('DIMENSION_WEIGHT'),
				'UNIT' => Market\Data\Weight::UNIT_GRAM,
			],
		];
	}

	protected function buildResult(Market\Trading\Service\Reference\Action\Response $response)
	{
		$this->arResult = [
			'ORDER' => $response->getField('order'),
			'WARNINGS' => $response->getField('warnings'),
			'PROPERTIES' => $response->getField('properties'),
			'BASKET' => [
				'COLUMNS' => $response->getField('basket.columns'),
				'ITEMS' => $response->getField('basket.items'),
				'SUMMARY' => $response->getField('basket.summary'),
			],
			'DELIVERY' => $response->getField('delivery'),
			'COURIER' => $response->getField('courier'),
			'BUYER' => $response->getField('buyer'),
			'SHIPMENT' => $response->getField('shipments'),
			'ORDER_ACTIONS' => (array)$response->getField('orderActions'),
			'PRINT_READY' => (bool)$response->getField('printReady'),
		];
	}

	protected function extendResult($orderExternalId, $orderNum, $orderInternalId)
	{
		$this->fillCommonData($orderExternalId, $orderNum, $orderInternalId);
		$this->fillBoxDimensions();
		$this->fillBasketItemsIndex();
		$this->fillBoxNumber($orderExternalId);
		$this->convertBoxDimensions();
		$this->resolveBasketCisColumn();
		$this->resolveBasketDigitalColumn();
		$this->filterOrderActions();
		$this->fillItemsChangeReason();
		$this->fillPrintDocuments();
		$this->fillBoxPacks();
		$this->resolveBoxSelectedPack();
		$this->extendActivities();
	}

	protected function fillCommonData($orderExternalId, $orderNum, $orderInternalId)
	{
		$this->arResult['SETUP_ID'] = $this->getSetup()->getId();
		$this->arResult['SERVICE_NAME'] = $this->getSetup()->getService()->getInfo()->getTitle('SHORT');
		$this->arResult['ORDER_INTERNAL_ID'] = $orderInternalId;
		$this->arResult['ORDER_EXTERNAL_ID'] = $orderExternalId;
		$this->arResult['ORDER_ACCOUNT_NUMBER'] = $orderNum;
	}

	protected function fillBoxDimensions()
	{
		$this->arResult['BOX_DIMENSIONS'] = $this->getBoxDimensions();
	}

	protected function fillBoxNumber($orderId)
	{
		if (empty($this->arResult['SHIPMENT'])) { return; }

		$boxNumber = 1;

		foreach ($this->arResult['SHIPMENT'] as &$shipment)
		{
			foreach ($shipment['BOX'] as &$box)
			{
				$box['NUMBER'] = $boxNumber;
				$box['FULFILMENT_ID'] = $orderId . '-' . $boxNumber;

				++$boxNumber;
			}
			unset($box);
		}
		unset($shipment);
	}

	protected function fillBasketItemsIndex()
	{
		if (empty($this->arResult['BASKET']['ITEMS'])) { return; }

		$basketItemIndex = 0;

		foreach ($this->arResult['BASKET']['ITEMS'] as &$basketItem)
		{
			if (isset($basketItem['INDEX']))
			{
				$basketItemIndex = max($basketItemIndex, $basketItem['INDEX'] + 1);
			}
			else
			{
				++$basketItemIndex;
				$basketItem['INDEX'] = $basketItemIndex;
			}
		}
		unset($basketItem);
	}

	protected function convertBoxDimensions()
	{
		if (empty($this->arResult['SHIPMENT']) || empty($this->arResult['BOX_DIMENSIONS'])) { return; }

		foreach ($this->arResult['SHIPMENT'] as &$shipment)
		{
			foreach ($shipment['BOX'] as &$box)
			{
				foreach ($this->arResult['BOX_DIMENSIONS'] as $dimensionName => $dimensionDescription)
				{
					if (!isset($box['DIMENSIONS'][$dimensionName])) { continue; }

					$boxDimension = &$box['DIMENSIONS'][$dimensionName];

					if (
						(string)$boxDimension['VALUE'] !== ''
						&& $boxDimension['UNIT'] !== $dimensionDescription['UNIT']
					)
					{
						$boxDimension['VALUE'] = $this->convertDimension(
							$dimensionName,
							$boxDimension['VALUE'],
							$boxDimension['UNIT'],
							$dimensionDescription['UNIT']
						);
						$boxDimension['UNIT'] = $dimensionDescription['UNIT'];
					}

					unset($boxDimension);
				}
			}
			unset($box);
		}
		unset($shipment);
	}

	protected function convertDimension($dimension, $value, $fromUnit, $toUnit)
	{
		if ($dimension === 'WEIGHT')
		{
			$result = Market\Data\Weight::convertUnit($value, $fromUnit, $toUnit);
		}
		else
		{
			$result = Market\Data\Size::convertUnit($value, $fromUnit, $toUnit);
		}

		return $result;
	}

	protected function resolveBasketCisColumn()
	{
		if (!isset($this->arResult['BASKET']['COLUMNS']['CIS'])) { return; }

		$isMarkingGroupUsed = false;

		foreach ($this->arResult['BASKET']['ITEMS'] as $item)
		{
			if (!empty($item['INSTANCE_TYPES']) || !empty($item['MARKING_GROUP']))
			{
				$isMarkingGroupUsed = true;
				break;
			}
		}

		if (!$isMarkingGroupUsed)
		{
			unset($this->arResult['BASKET']['COLUMNS']['CIS']);
		}
	}

	protected function resolveBasketDigitalColumn()
	{
		if (
			isset($this->arResult['BASKET']['COLUMNS']['DIGITAL'])
			&& empty($this->arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::DIGITAL])
		)
		{
			unset($this->arResult['BASKET']['COLUMNS']['DIGITAL']);
		}
	}

	protected function filterOrderActions()
	{
		if (empty($this->arResult['ORDER_ACTIONS'])) { return; }

		$this->arResult['ORDER_ACTIONS'] = array_intersect_key(
			$this->arResult['ORDER_ACTIONS'],
			$this->getReadyActions()
		);
	}

	protected function getReadyActions()
	{
		return array_filter([
			TradingEntity\Operation\Order::ITEM => !empty($this->arResult['BASKET']['ITEMS']),
			TradingEntity\Operation\Order::BOX => !empty($this->arResult['SHIPMENT']),
			TradingEntity\Operation\Order::CIS => isset($this->arResult['BASKET']['COLUMNS']['CIS']),
			TradingEntity\Operation\Order::DIGITAL => isset($this->arResult['BASKET']['COLUMNS']['DIGITAL']),
		]);
	}

	protected function fillItemsChangeReason()
	{
		if (!isset($this->arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::ITEM])) { return; }

		$service = $this->getSetup()->getService();

		if (!($service instanceof Market\Trading\Service\Reference\HasItemsChangeReason)) { return; }

		$reasonService = $service->getItemsChangeReason();
		$enum = [];

		foreach ($reasonService->getVariants() as $variant)
		{
			$enum[] = [
				'ID' => $variant,
				'VALUE' => $reasonService->getTitle($variant),
			];
		}

		$this->arResult['ITEMS_CHANGE_REASON'] = $enum;
	}

	protected function fillPrintDocuments()
	{
		$printer = $this->getSetup()->getService()->getPrinter();
		$documents = [];

		foreach ($printer->getTypes() as $type)
		{
			$document = $printer->getDocument($type);

			if ($document->getSourceType() !== Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER) { continue; }
			if (!$this->matchActivityFilter($document->getFilter())) { continue; }

			$documents[] = [
				'TYPE' => $type,
				'TITLE' => $document->getTitle(),
			];
		}

		$this->arResult['PRINT_DOCUMENTS'] = $documents;
	}

	protected function fillBoxPacks()
	{
		$query = Market\Ui\Trading\Internals\PackTable::getList();

		$this->arResult['BOX_PACKS'] = $query->fetchAll();
	}

	protected function resolveBoxSelectedPack()
	{
		if (empty($this->arResult['SHIPMENT']) || empty($this->arResult['BOX_DIMENSIONS'])) { return; }

		foreach ($this->arResult['SHIPMENT'] as &$shipment)
		{
			foreach ($shipment['BOX'] as &$box)
			{
				$selectedPack = null;

				foreach ($this->arResult['BOX_PACKS'] as $pack)
				{
					$isMatchedPack = true;

					foreach ($this->arResult['BOX_DIMENSIONS'] as $dimensionName => $dimensionDescription)
					{
						if ($dimensionName === 'WEIGHT') { continue; }
						if (!isset($pack[$dimensionName], $box['DIMENSIONS'][$dimensionName])) { continue; }

						if ((int)$pack[$dimensionName] !== (int)$box['DIMENSIONS'][$dimensionName]['VALUE'])
						{
							$isMatchedPack = false;
							break;
						}
					}

					if ($isMatchedPack)
					{
						$selectedPack = $pack['ID'];
						break;
					}
				}

				$box['PACK'] = $selectedPack;
			}
			unset($box);
		}
		unset($shipment);
	}

	protected function extendActivities()
	{
		$resultKeys = [
			'PROPERTIES',
			'DELIVERY',
			'BUYER',
		];

		foreach ($resultKeys as $resultKey)
		{
			if (!isset($this->arResult[$resultKey])) { continue; }

			foreach ($this->arResult[$resultKey] as $propertyKey => &$property)
			{
				if (!isset($property['ACTIVITY'])) { continue; }

				$activity = $this->getActivity($property['ACTIVITY']);
				$activityAction = $this->makeActivityAction($property['ACTIVITY'], $activity);

				if ($this->matchActivityFilter($activityAction['FILTER']))
				{
					$property['ACTIVITY_ACTION'] = $activityAction;
				}
				else if (Market\Utils\Value::isEmpty($property['VALUE']))
				{
					unset($this->arResult[$resultKey][$propertyKey]);
				}
			}
			unset($property);

			if (empty($this->arResult[$resultKey]))
			{
				unset($this->arResult[$resultKey]);
			}
		}
	}

	protected function makeActivityAction($path, Market\Trading\Service\Reference\Action\AbstractActivity $activity, $chain = '')
	{
		$type = $path . ($chain !== '' ? '|' . $chain : '');
		$result = [
			'TYPE' => $path,
			'TEXT' => $activity->getTitle(),
			'FILTER' => $activity->getFilter(),
		];

		if ($activity instanceof Market\Trading\Service\Reference\Action\ComplexActivity)
		{
			$result['MENU'] = [];

			foreach ($activity->getActivities() as $key => $child)
			{
				$childChain = ($chain !== '' ? $chain . '.' . $key : $key);

				$result['MENU'][] = $this->makeActivityAction($path, $child, $childChain);
			}
		}
		else if ($activity instanceof Market\Trading\Service\Reference\Action\CommandActivity)
		{
			$result['METHOD'] = sprintf(
				'BX.YandexMarket.OrderView.activity.executeCommand("%s")',
				$type
			);

			$result += $activity->getParameters(); // confirm and etc
		}
		else if ($activity instanceof Market\Trading\Service\Reference\Action\FormActivity)
		{
			$result['METHOD'] = sprintf(
				'BX.YandexMarket.OrderView.activity.openForm("%s", %s)',
				$type,
				Main\Web\Json::encode([
					'TITLE' => $result['TEXT'],
				])
			);
		}
		else if ($activity instanceof Market\Trading\Service\Reference\Action\ViewActivity)
		{
			$result['METHOD'] = sprintf(
				'BX.YandexMarket.OrderView.activity.openView("%s", %s)',
				$type,
				Main\Web\Json::encode([
					'TITLE' => $result['TEXT'],
				])
			);
		}

		return $result;
	}

	protected function getActivity($path)
	{
		/** @var Market\Trading\Service\Reference\Action\HasActivity $action */
		$setup = $this->getSetup();
		$router = $setup->wakeupService()->getRouter();

		return $router->getActivity($path, $setup->getEnvironment());
	}

	protected function matchActivityFilter($filter)
	{
		return Market\Utils\ActionFilter::isMatch($filter, $this->arResult['ORDER']);
	}
}