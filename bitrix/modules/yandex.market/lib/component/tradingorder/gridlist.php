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
			$this->orderFields = $this->loadOrderFields();
		}

		return $this->orderFields;
	}

	protected function loadOrderFields()
	{
		return $this->makeFields([
			'ID' => [
				'TYPE' => 'primary',
				'NAME' => static::getLang('COMPONENT_TRADING_ORDER_LIST_FIELD_ID', [
					'#SERVICE_NAME#' => $this->getSetup()->getService()->getInfo()->getTitle('DATIVE'),
				]),
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SETTINGS' => [
					'URL_FIELD' => 'SERVICE_URL',
				],
			],
			'ORDER_ID' => [
				'TYPE' => 'primary',
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SETTINGS' => [
					'URL_FIELD' => 'EDIT_URL',
				],
			],
			'ACCOUNT_NUMBER' => [
				'TYPE' => 'primary',
				'FILTERABLE' => false,
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
				'TYPE' => 'date',
				'MULTIPLE' => 'Y',
				'SORTABLE' => false,
			],
			'BASKET' => [
				'TYPE' => 'tradingOrderItem',
				'MULTIPLE' => 'Y',
				'FILTERABLE' => false,
				'SORTABLE' => false,
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
				'TYPE' => 'string',
				'FILTERABLE' => false,
				'SORTABLE' => false,
			],
			'FAKE' => [
				'TYPE' => 'boolean',
				'SORTABLE' => false,
			],
		]);
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

	protected function makeFields($fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
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

			$result[$name] = $userField;
		}

		return $result;
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
			if (empty($item['PRINT_READY']))
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

					case '>=DATE_CREATE':
						$result['fromDate'] = new Main\Type\DateTime($value);
					break;

					case '<=DATE_CREATE':
						$result['toDate'] = new Main\Type\DateTime($value);
					break;

					case '>=DATE_SHIPMENT':
						$result['fromShipmentDate'] = new Main\Type\Date($value);
					break;

					case '<=DATE_SHIPMENT':
						$result['toShipmentDate'] = new Main\Type\Date($value);
					break;

					case 'FAKE':
						$result['fake'] = ((string)$value === '1');
					break;
				}
			}
		}

		return $result;
	}

	protected function getDefaultFetchParameters()
	{
		$isLoadMoreAction = $this->isLoadMoreAction();

		return [
			'flushCache' => !$isLoadMoreAction,
			'useCache' => true,
		];
	}

	protected function getEnvironmentFetchParameters()
	{
		global $USER;

		$accessParameter = $this->getComponentParam('CHECK_ACCESS');

		return [
			'userId' => $USER->GetID(),
			'checkAccess' => isset($accessParameter) ? (bool)$accessParameter : true,
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
		if (!isset($item['EDIT_URL']))
		{
			foreach ($actions as $actionIndex => $action)
			{
				if ($action['TYPE'] === 'EDIT')
				{
					unset($actions[$actionIndex]);
				}
			}
		}

		if (empty($item['PRINT_READY']))
		{
			foreach ($actions as $actionIndex => $action)
			{
				if (Market\Data\TextString::getPosition($action['TYPE'], 'PRINT_') === 0)
				{
					unset($actions[$actionIndex]);
				}
			}
		}

		return $actions;
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