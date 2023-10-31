<?php

namespace Yandex\Market\Component\TradingShipment;

use Bitrix\Main;
use Yandex\Market;

class GridList extends Market\Component\Base\GridList
{
	use Market\Reference\Concerns\HasMessage;

	const PAGE_SIZE = 30;

	protected $shipmentFields;
	protected $setup;
	protected $paging;

	public function __construct(\CBitrixComponent $component)
	{
		parent::__construct($component);

		$this->paging = new Market\Component\Molecules\ApiPaging($this->getComponentParam('GRID_ID'));
	}

	public function getFields(array $select = [])
	{
		$result = $this->getShipmentFields();

		if (!empty($select))
		{
			$selectMap = array_flip($select);
			$result = array_intersect_key($result, $selectMap);
		}

		return $result;
	}

	protected function getShipmentFields()
	{
		if ($this->shipmentFields === null)
		{
			$this->shipmentFields = $this->loadShipmentFields();
		}

		return $this->shipmentFields;
	}

	protected function loadShipmentFields()
	{
		return $this->makeFields([
			'ID' => [
				'TYPE' => 'primary',
				'SETTINGS' => [
					'URL_FIELD' => 'SERVICE_URL',
				],
			],
			'EXTERNAL_ID' => [
				'TYPE' => 'primary',
				'SETTINGS' => [
					'URL_FIELD' => 'SERVICE_URL',
				],
			],
			'DATE' => [
				'TYPE' => 'dateTimePeriod',
				'FILTERABLE' => true,
			],
			'STATUS' => [
				'TYPE' => 'enumeration',
				'VALUES' => $this->getStatusEnum(),
				'FILTERABLE' => true,
				'SETTINGS' => [
					'DESCRIPTION_FIELD' => 'STATUS_DESCRIPTION',
				],
			],
			'STATUS_DESCRIPTION' => [
				'TYPE' => 'string',
			],
			'SHIPMENT_TYPE' => [
				'TYPE' => 'enumeration',
				'VALUES' => $this->getShipmentTypeEnum(),
			],
			'DELIVERY_SERVICE' => [
				'TYPE' => 'string',
			],
			'DRAFT_COUNT' => [
				'TYPE' => 'number',
			],
			'PLANNED_COUNT' => [
				'TYPE' => 'number',
			],
			'FACT_COUNT' => [
				'TYPE' => 'number',
			],
			'ORDER_ID' => [
				'TYPE' => 'string',
				'FILTERABLE' => true,
				'SELECTABLE' => false,
			]
		]);
	}

	protected function getStatusEnum()
	{
		$result = [];
		$types = [
			'OUTBOUND_CREATED',
			'OUTBOUND_READY_FOR_CONFIRMATION',
			'OUTBOUND_CONFIRMED',
			'ACCEPTED',
			'ACCEPTED_WITH_DISCREPANCIES',
			'FINISHED',
			'ERROR',
		];

		foreach ($types as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => self::getMessage('FIELD_STATUS_ENUM_' . $type, null, $type),
			];
		}

		return $result;
	}

	protected function getShipmentTypeEnum()
	{
		$result = [];
		$types = [
			'IMPORT',
			'WITHDRAW',
		];

		foreach ($types as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => self::getMessage('FIELD_SHIPMENT_TYPE_ENUM_' . $type, null, $type),
			];
		}

		return $result;
	}

	protected function makeFields($fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$field += [
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'NAME' => self::getMessage('FIELD_' . $name, null, $name),
			];

			$result[$name] = Market\Ui\UserField\Helper\Field::extend($field, $name);
		}

		return $result;
	}

	public function load(array $queryParameters = [])
	{
		try
		{
			$procedure = new Market\Trading\Procedure\Runner(Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER, null);
			$setup = $this->getSetup();
			$service = $setup->wakeupService();
			$logger = $service->getLogger();

			$fetchParameters = $this->convertQueryToFetchParameters($queryParameters);

			$this->configureLogger($logger);

			$response = $procedure->run($setup, 'admin/shipments', $fetchParameters);
			$lastPage = $response->getField('nextPage');

			if ($lastPage !== null)
			{
				$this->paging->setPageToken($fetchParameters, $lastPage, $response->getField('nextPageToken'));
			}
			else
			{
				$lastPage = isset($fetchParameters['page']) ? $fetchParameters['page'] : 1;
			}

			return [
				'ITEMS' => (array)$response->getField('shipments'),
				'TOTAL_COUNT' => $lastPage * static::PAGE_SIZE,
			];
		}
		catch (Main\ArgumentException $exception)
		{
			if ($exception->getParameter() !== 'pageToken') { throw $exception; }

			$lastPage = isset($fetchParameters['page']) ? $fetchParameters['page'] : 1;

			return [
				'ITEMS' => [],
				'TOTAL_COUNT' => $lastPage * static::PAGE_SIZE,
			];
		}
	}

	protected function configureLogger($logger)
	{
		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$logger->setLevel(Market\Logger\Level::ERROR);
		}
	}

	protected function convertQueryToFetchParameters($queryParameters)
	{
		$result = [];
		$defaults = [
			'dateFrom' => (new Main\Type\DateTime())->add('-P1M'),
			'dateTo' => (new Main\Type\DateTime())->add('P1M'),
		];

		if (isset($queryParameters['filter']))
		{
			foreach ($queryParameters['filter'] as $key => $value)
			{
				switch ($key)
				{
					case '>=DATE':
						$dateFrom = new Main\Type\DateTime($value);
						$dateTo = clone $dateFrom;

						$result['dateFrom'] = $dateFrom;
						$defaults['dateTo'] = $dateTo->add('P2M');
					break;

					case '<=DATE':
						$dateTo = new Main\Type\DateTime($value);
						$dateFrom = clone $dateTo;

						$result['dateTo'] = $dateTo->add('P1D');
						$defaults['dateFrom'] = $dateFrom->add('-P2M');
					break;

					case 'STATUS':
						$result['statuses'] = (array)$value;
					break;

					case 'ORDER_ID':
						$result['orderIds'] = (array)$value;
					break;
				}
			}
		}

		$result += $defaults;
		$result += $this->paging->getParameters($queryParameters, static::PAGE_SIZE);

		return $result;
	}

	public function loadTotalCount(array $queryParameters = [])
	{
		return null;
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

	public function filterActions($item, $actions)
	{
		foreach ($actions as $actionIndex => &$action)
		{
			if ($action['TYPE'] === 'PRINT')
			{
				$isValid = !empty($item['PRINT_READY']);
			}
			else if (isset($action['FILTER']) && is_array($action['FILTER']))
			{
				$isValid = (count(array_diff_assoc($action['FILTER'], $item)) === 0);
			}
			else
			{
				$isValid = true;
			}

			if (!$isValid)
			{
				unset($actions[$actionIndex]);
			}
		}
		unset($action);

		return $actions;
	}
}