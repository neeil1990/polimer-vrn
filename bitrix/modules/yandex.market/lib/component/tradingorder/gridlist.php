<?php

namespace Yandex\Market\Component\TradingOrder;

use Bitrix\Main;
use Yandex\Market;

class GridList extends Market\Component\Base\GridList
{
	use Market\Reference\Concerns\HasLang;

	protected $orderFields;
	protected $setup;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function processAjaxAction($action, $data)
	{
		if ($action === 'boxes')
		{
			$count = $this->getAjaxActionBoxesCount();

			$this->processOrderAction($data, 'sendBoxes', $count);
		}
		else if ($action === 'accept')
        {
            $this->processOrderAction($data, 'emulateAccept', null);
        }
		else
		{
			parent::processAjaxAction($action, $data);
		}
	}

	protected function getAjaxActionBoxesCount()
	{
		if (!isset($_REQUEST['boxes']))
		{
			throw new Main\ArgumentException('boxes count is missing');
		}

		return (string)$_REQUEST['boxes'];
	}

	protected function processOrderAction($actionData, $method, $payload)
	{
		$errorMessages = [];
		$hasSuccess = false;

		foreach ($this->getActionSelectedIds($actionData) as $externalId)
		{
			$sendResult = $this->{$method}($externalId, $payload);

			if ($sendResult->isSuccess())
			{
				$hasSuccess = true;
			}
			else
			{
				$errorMessages[] = implode('<br />', $sendResult->getErrorMessages());
			}
		}

		if ($hasSuccess)
		{
			Market\Trading\State\SessionCache::releaseByType('order');
		}

		if (!empty($errorMessages))
		{
			throw new Main\SystemException(implode('<br />', $errorMessages));
		}
	}

	protected function getActionSelectedIds($data)
	{
		if (!empty($data['IS_ALL']))
		{
			throw new Main\NotSupportedException();
		}

		return (array)$data['ID'];
	}

	/** @noinspection PhpUnused */
	protected function sendBoxes($externalId, $count)
	{
		$result = new Main\Result();

		try
		{
			$setup = $this->getSetup();
			$accountNumber = $this->getOrderNumber($externalId);
			$shipmentId = $this->getOrderShipmentId($externalId);

			$procedure = new Market\Trading\Procedure\Runner(
				Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
				$accountNumber
			);

			$procedure->run($setup, 'send/boxes', [
				'orderId' => $externalId,
				'orderNum' => $accountNumber,
				'shipmentId' => $shipmentId,
				'boxes' => $this->makeBoxes($externalId, $count),
			]);
		}
		catch (Main\SystemException $exception)
		{
			$exceptionMessage = $exception->getMessage();
			$message = static::getLang('COMPONENT_TRADING_ORDER_LIST_ORDER_ACTION_FAILED', [
				'#ORDER_ID#' => $externalId,
				'#MESSAGE#' => $exceptionMessage,
			], $exceptionMessage);

			$result->addError(new Main\Error($message));
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function emulateAccept($externalId)
    {
        $result = new Main\Result();

        try
        {
            $setup = $this->getSetup();
            $service = $setup->wakeupService();
            $options = $service->getOptions();

            $orderFacade = $service->getModelFactory()->getOrderFacadeClassName();
            $order = $orderFacade::load($options, $externalId);

            static::emulateAction($setup, 'order/accept', $order);
            static::emulateAction($setup, 'order/status', $order);
        }
        catch (Main\SystemException $exception)
        {
            $result->addError(new Main\Error($exception->getMessage()));
        }

        return $result;
    }

	protected static function emulateAction(Market\Trading\Setup\Model $setup, $path, Market\Api\Model\Order $order)
	{
		$environment = $setup->getEnvironment();
		$service = $setup->wakeupService();
		/** @noinspection NullPointerExceptionInspection */
		$server = Main\Context::getCurrent()->getServer();
		$request = static::makeRequestFromOrder($server, $order);

		$action = $service->getRouter()->getHttpAction($path, $environment, $request, $server);
		$action->process();
	}

	protected static function makeRequestFromOrder(Main\Server $server, Market\Api\Model\Order $order)
	{
		return new Main\HttpRequest(
			$server,
			[], // query string
			[
				'order' => $order->getFields(),
				'emulated' => true,
				'download' => true,
			], // post
			[], // files
			[] // cookies
		);
	}

	protected function getOrderNumber($externalId, $useAccountNumber = null)
	{
		$setup = $this->getSetup();
		$platform = $setup->getPlatform();
		$orderRegistry = $setup->getEnvironment()->getOrderRegistry();

		return $orderRegistry->search($externalId, $platform, $useAccountNumber);
	}

	protected function getOrderShipmentId($externalId)
	{
		$uniqueKey = $this->getSetup()->getService()->getUniqueKey();

		return Market\Trading\State\OrderData::getValue($uniqueKey, $externalId, 'SHIPMENT_ID');
	}

	protected function makeBoxes($externalId, $count)
	{
		$result = [];

		for ($index = 1; $index <= $count; ++$index)
		{
			$result[] = [
				'fulfilmentId' => $externalId . '-' . $index,
			];
		}

		return $result;
	}

	public function getFields(array $select = [])
	{
		$result = $this->getOrderFields();

		if (!empty($select))
		{
			$selectMap = array_flip($select);
			$result = array_intersect_key($result, $selectMap);
		}

		return $result;
	}

	protected function getOrderFields()
	{
		if ($this->orderFields === null)
		{
			$fields = $this->loadOrderFields();
			$fields = $this->filterSupportsFields($fields);

			$this->orderFields = $fields;
		}

		return $this->orderFields;
	}

	protected function loadOrderFields()
	{
		return $this->makeFields([
			'ID' => [
				'TYPE' => 'compound',
				'NAME' => static::getLang('COMPONENT_TRADING_ORDER_LIST_FIELD_ID', [
					'#SERVICE_NAME#' => $this->getSetup()->getService()->getInfo()->getTitle('DATIVE'),
				]),
				'SORTABLE' => false,
				'FIELDS' => [
					'ID' => [
						'TYPE' => 'primary',
						'SETTINGS' => [
							'URL_FIELD' => 'SERVICE_URL',
						],
					],
					'BUYER_TYPE' => [
						'SKIP' => [
							Market\Trading\Service\Marketplace\Model\Order\Buyer::TYPE_PERSON,
						],
					],
				],
			],
			'ORDER_ID' => [
				'TYPE' => 'primary',
				'SORTABLE' => false,
				'SETTINGS' => [
					'URL_FIELD' => 'EDIT_URL',
				],
			],
			'ACCOUNT_NUMBER' => [
				'TYPE' => 'primary',
				'SORTABLE' => false,
				'SETTINGS' => [
					'URL_FIELD' => 'EDIT_URL',
				],
			],
			'DATE_CREATE' => [
				'TYPE' => 'datetime',
				'SORTABLE' => false,
			],
			'DATE_SHIPMENT' => [
				'TYPE' => 'datetime',
				'MULTIPLE' => 'Y',
				'SORTABLE' => false,
			],
			'DATE_DELIVERY' => [
				'TYPE' => 'dateTimePeriod',
				'SORTABLE' => false,
			],
			'OUTLET_STORAGE_LIMIT_DATE' => [
				'TYPE' => 'date',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DBS,
				],
			],
			'EXPIRY_DATE' => [
				'TYPE' => 'date',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DEFAULT,
				],
			],
			'BUYER_TYPE' => [
				'TYPE' => 'enumeration',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'VALUES' => $this->getBuyerTypeEnum(),
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DEFAULT,
				],
			],
			'BASKET' => [
				'TYPE' => 'tradingOrderItem',
				'MULTIPLE' => 'Y',
				'FILTERABLE' => false,
				'SORTABLE' => false,
			],
			'BOX_COUNT' => [
				'TYPE' => 'number',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SETTINGS' => [
					'UNIT' => static::getLang('COMPONENT_TRADING_ORDER_LIST_FIELD_BOX_COUNT_UNIT'),
				],
			],
			'TOTAL' => [
				'TYPE' => 'price',
				'FILTERABLE' => false,
				'SORTABLE' => false,
			],
			'SUBSIDY' => [
				'TYPE' => 'price',
				'FILTERABLE' => false,
				'SORTABLE' => false,
			],
			'STATUS' => [
				'TYPE' => 'enumeration',
				'SELECTABLE' => false,
				'SORTABLE' => false,
				'VALUES' => $this->getStatusEnum(),
			],
			'STATUS_LANG' => [
				'TYPE' => 'compound',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'FIELDS' => [
					'STATUS_LANG' => [
						'TYPE' => 'string',
					],
					'EXPIRY_DATE',
					'OUTLET_STORAGE_LIMIT_DATE',
					'DATE_SHIPMENT',
					'DATE_DELIVERY',
					'EAC_CODE' => [
						'TYPE' => 'string',
						'NAME' => static::getLang('COMPONENT_TRADING_ORDER_LIST_FIELD_EAC_CODE'),
					],
				],
				'FILTER' => [
					'EXPIRY_DATE' => [
						'STATUS' => [
							Market\Trading\Service\Marketplace\Status::STATUS_UNPAID,
							Market\Trading\Service\Marketplace\Status::STATUS_PENDING,
						],
					],
					'DATE_SHIPMENT' => [
						'STATUS' => Market\Trading\Service\Marketplace\Status::STATUS_PROCESSING,
					],
					'OUTLET_STORAGE_LIMIT_DATE' => [
						'STATUS' => Market\Trading\Service\Marketplace\Status::STATUS_PICKUP,
					],
					'DATE_DELIVERY' => false,
				],
			],
			'FAKE' => [
				'TYPE' => 'boolean',
				'SORTABLE' => false,
				'SELECTABLE' => false,
			],
			'HAS_CIS' => [
				'TYPE' => 'boolean',
				'SELECTABLE' => false,
				'SORTABLE' => false,
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DEFAULT,
				],
			],
			'DISPATCH_TYPE' => [
				'TYPE' => 'enumeration',
				'SORTABLE' => false,
				'SELECTABLE' => false,
				'VALUES' => $this->getDispatchTypeEnum(),
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DBS,
				],
			],
			'WAIT_CANCELLATION_APPROVE' => [
				'TYPE' => 'boolean',
				'SORTABLE' => false,
				'SELECTABLE' => false,
				'SUPPORTS' => [
					Market\Trading\Service\Manager::SERVICE_MARKETPLACE . ':' . Market\Trading\Service\Manager::BEHAVIOR_DBS,
				],
			],
		]);
	}

	protected function filterSupportsFields(array $fields)
	{
		$setup = $this->getSetup();
		$match = [
			$setup->getServiceCode(),
			$setup->getServiceCode() . ':' . $setup->getBehaviorCode(),
		];

		foreach ($fields as $key => $field)
		{
			if (!isset($field['SUPPORTS'])) { continue; }

			$intersect = array_intersect((array)$field['SUPPORTS'], $match);

			if (empty($intersect))
			{
				unset($fields[$key]);
				continue;
			}

			if (isset($field['FIELDS']))
			{
				$field['FIELDS'] = $this->filterSupportsFields($field['FIELDS']);

				if (empty($field['FIELDS']))
				{
					unset($fields[$key]);
				}
			}
		}

		return $fields;
	}

	protected function getBuyerTypeEnum()
	{
		$types = [
			Market\Trading\Service\Marketplace\Model\Order\Buyer::TYPE_PERSON,
			Market\Trading\Service\Marketplace\Model\Order\Buyer::TYPE_BUSINESS,
		];
		$result = [];

		foreach ($types as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => Market\Trading\Service\Marketplace\Model\Order\Buyer::getTypeTitle($type),
			];
		}

		return $result;
	}

	protected function getStatusEnum()
	{
		$serviceStatus = $this->getSetup()->getService()->getStatus();
		$result = [];

		foreach ($serviceStatus->getVariants() as $status)
		{
			$result[] = [
				'ID' => $status,
				'VALUE' => $serviceStatus->getTitle($status, 'SHORT'),
			];
		}

		return $result;
	}

	protected function getDispatchTypeEnum()
	{
		$service = $this->getSetup()->getService();

		if (!method_exists($service, 'getDelivery')) { return []; }

		$serviceDelivery = $service->getDelivery();

		if (!($serviceDelivery instanceof Market\Trading\Service\MarketplaceDbs\Delivery)) { return []; }

		$result = [];

		foreach ($serviceDelivery->getDispatchTypes() as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => $serviceDelivery->getDispatchTypeTitle($type),
			];
		}

		return $result;
	}

	protected function makeFields($fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$result[$name] = $this->extendField($name, $field);
		}

		return $this->compileCompoundFields($result);
	}

	protected function compileCompoundFields(array $fields)
	{
		foreach ($fields as &$field)
		{
			if (!isset($field['TYPE']) || $field['TYPE'] !== 'compound') { continue; }

			$children = [];

			foreach ($field['FIELDS'] as $childName => $child)
			{
				if (is_numeric($childName))
				{
					$childName = $child;
					$child = $fields[$child];
				}
				else if (isset($fields[$childName]))
				{
					$child += array_diff_key($fields[$childName], [
						'USER_TYPE' => true,
					]);
				}

				$children[$childName] = $this->extendField($childName, $child);
			}

			$field['FIELDS'] = $children;
		}
		unset($field);

		return $fields;
	}

	protected function extendField($name, array $field)
	{
		$userField = $field;
		$fieldTitle = isset($field['NAME'])
			? $field['NAME']
			: static::getLang('COMPONENT_TRADING_ORDER_LIST_FIELD_' . $name);

		if (!isset($field['USER_TYPE']) && isset($field['TYPE']))
		{
			$userField['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($field['TYPE']);
		}

		$userField += [
			'MULTIPLE' => 'N',
			'EDIT_IN_LIST' => 'Y',
			'EDIT_FORM_LABEL' => $fieldTitle,
			'FIELD_NAME' => $name,
			'SETTINGS' => [],
		];

		return $userField;
	}

	public function load(array $queryParameters = [])
	{
		$procedure = new Market\Trading\Procedure\Runner(Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER, null);
		$setup = $this->getSetup();
		$service = $setup->wakeupService();
		$logger = $service->getLogger();

		$fetchParameters =
			$this->convertQueryToFetchParameters($queryParameters)
			+ $this->getDefaultFetchParameters()
			+ $this->getEnvironmentFetchParameters();

		$this->configureLogger($logger);

		$response = $procedure->run($setup, 'admin/list', $fetchParameters);

		$orders = $response->getField('orders');
		$totalCount = $response->getField('totalCount');

		return [
			'ITEMS' => $this->extendItems($orders),
			'TOTAL_COUNT' => $totalCount,
		];
	}

	protected function configureLogger($logger)
	{
		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$logger->setLevel(Market\Logger\Level::ERROR);
		}
	}

	protected function extendItems($items)
	{
		foreach ($items as &$item)
		{
			$allowStatus = $item['STATUS_READY'] && !empty($item['STATUS_ALLOW']);
			$allowCancel = $item['CANCEL_ALLOW'];
			$allowPrint = $item['PRINT_READY'];
			$isProcessing = $item['PROCESSING'];

			if (!$allowStatus && !$allowCancel && !$allowPrint && !$isProcessing)
			{
				$item['DISABLED'] = true;
			}
		}
		unset($item);

		return $items;
	}

	protected function convertQueryToFetchParameters($queryParameters)
	{
		$result = [];

		if (isset($queryParameters['limit'], $queryParameters['offset']))
		{
			$result['pageSize'] = $queryParameters['limit'];
			$result['page'] = floor($queryParameters['offset'] / $queryParameters['limit']) + 1;
		}

		if (isset($queryParameters['filter']))
		{
			foreach ($queryParameters['filter'] as $key => $value)
			{
				switch ($key)
				{
					case 'STATUS':
						$result['status'] = $value;
					break;

					case 'DISPATCH_TYPE':
						$result['dispatchType'] = $value;
					break;

					case '>=DATE_CREATE':
						$result['fromDate'] = new Main\Type\DateTime($value);
					break;

					case '<=DATE_CREATE':
						$result['toDate'] = $this->roundFilterUntilDate(new Main\Type\DateTime($value));
					break;

					case '>=DATE_SHIPMENT':
						$result['fromShipmentDate'] = new Main\Type\DateTime($value);
					break;

					case '<=DATE_SHIPMENT':
						$result['toShipmentDate'] = $this->roundFilterUntilDate(new Main\Type\DateTime($value));
					break;

					case 'FAKE':
						$result['fake'] = ((string)$value === '1');
					break;

					case 'HAS_CIS':
						$result['hasCis'] = ((string)$value === '1');
					break;

					case 'ID':
						$ids = $this->searchExternalIds($value, 'EXTERNAL_ORDER_ID') ?: (array)$value;

						$result['id'] = isset($result['id']) ? array_intersect($result['id'], $ids) : $ids;
					break;

					case 'ORDER_ID':
						$ids = $this->searchExternalIds($value, 'ORDER_ID');

						$result['id'] = isset($result['id']) ? array_intersect($result['id'], $ids) : $ids;
					break;

					case 'ACCOUNT_NUMBER':
						$ids =
							$this->searchExternalIds($value, 'ACCOUNT_NUMBER')
							?: $this->searchExternalIds($value, 'ORDER_ID');

						$result['id'] = isset($result['id']) ? array_intersect($result['id'], $ids) : $ids;
					break;

					case 'WAIT_CANCELLATION_APPROVE':
						$result['onlyWaitingForCancellationApprove'] = ((string)$value === '1');
					break;
				}
			}
		}

		$result = $this->limitFilterDatesGap($result, 'fromDate', 'toDate', 30);
		$result = $this->limitFilterDatesGap($result, 'fromShipmentDate', 'toShipmentDate', 30);

		return $result;
	}

	protected function roundFilterUntilDate(Main\Type\DateTime $date)
	{
		$date = new Main\Type\DateTime($date);
		$time = $date->format('H:i');

		if ($time === '23:59')
		{
			$date->add('PT1S');
		}
		else if ($time === '00:00')
		{
			$date->add('P1D');
		}

		return $date;
	}

	protected function limitFilterDatesGap($filter, $fromName, $toName, $limit)
	{
		if (!isset($filter[$fromName], $filter[$toName])) { return $filter; }

		$from = $filter[$fromName];
		$to = $filter[$toName];

		if (!($from instanceof Main\Type\Date) || !($to instanceof Main\Type\Date)) { return $filter; }

		$fromLimit = clone $to;
		$fromLimit->add(sprintf('-P%sD', $limit));

		if (Market\Data\Date::compare($fromLimit, $from) === 1)
		{
			$filter[$fromName] = $fromLimit;
		}

		return $filter;
	}

	protected function searchExternalIds($value, $field)
	{
		$orderRegistry = $this->getSetup()->getEnvironment()->getOrderRegistry();
		$platform = $this->getSetup()->getPlatform();

		return $orderRegistry->suggestExternalIds($value, $field, $platform);
	}

	protected function getDefaultFetchParameters()
	{
		$isLoadMoreAction = $this->isLoadMoreAction();

		return [
			'flushCache' => !$isLoadMoreAction,
			'useCache' => true,
			'suppressErrors' => true,
		];
	}

	protected function getEnvironmentFetchParameters()
	{
		global $USER;

		$accessParameter = $this->getComponentParam('CHECK_ACCESS');

		return [
			'userId' => $USER->GetID(),
			'checkAccess' => !isset($accessParameter) || $accessParameter,
		];
	}

	protected function isLoadMoreAction()
	{
		return $_REQUEST['mode'] === 'loadMore';
	}

	public function loadTotalCount(array $queryParameters = [])
	{
		return null;
	}

	public function filterActions($item, $actions)
	{
		foreach ($actions as $actionIndex => &$action)
		{
			if ($action['TYPE'] === 'ACCEPT')
			{
				$isValid = empty($item['ORDER_ID']);
			}
			else if (empty($item['ORDER_ID']))
			{
				$isValid = false;
			}
			else if ($action['TYPE'] === 'EDIT')
			{
				$isValid = isset($item['EDIT_URL']);
			}
			else if ($action['TYPE'] === 'PRINT')
			{
				$isValid = !empty($item['PRINT_READY']);
			}
			else
			{
				$isValid = $this->matchActionFilter($action, $item);
			}

			if ($isValid && isset($action['MENU']))
			{
				$action['MENU'] = $this->filterActionMenu($action['MENU'], $item);
				$isValid = !empty($action['MENU']);
			}

			if (!$isValid)
			{
				unset($actions[$actionIndex]);
			}
		}
		unset($action);

		return $actions;
	}

	protected function filterActionMenu($menu, $item)
	{
		foreach ($menu as $menuKey => $menuAction)
		{
			if (!$this->matchActionFilter($menuAction, $item))
			{
				unset($menu[$menuKey]);
			}
		}

		return array_values($menu);
	}

	protected function matchActionFilter($action, $item)
	{
		if (!isset($action['FILTER'])) { return true; }

		return Market\Utils\ActionFilter::isMatch($action['FILTER'], $item);
	}

	public function getSetup()
	{
		if ($this->setup === null)
		{
			$this->setup = $this->loadSetup();
		}

		return $this->setup;
	}

	protected function loadSetup()
	{
		$setupId = (int)$this->getComponentParam('SETUP_ID');

		return Market\Trading\Setup\Model::loadById($setupId);
	}

	public function getRequiredParams()
	{
		return [
			'SETUP_ID',
		];
	}

	public function getDefaultFilter()
	{
		return [];
	}

	public function getDefaultSort()
	{
		return [];
	}

	public function deleteItem($id)
	{
		throw new Main\NotSupportedException();
	}
}