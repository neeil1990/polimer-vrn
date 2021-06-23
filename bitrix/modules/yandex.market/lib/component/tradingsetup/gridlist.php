<?php

namespace Yandex\Market\Component\TradingSetup;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Entity as TradingEntity;

class GridList extends Market\Component\Model\GridList
{
	use Market\Component\Concerns\HasUiService;
	use Market\Component\Concerns\HasCalculatedFields;

	protected $titleResolved = false;
	protected $calculatedFields;
	protected $repository;

	public function prepareComponentParams($params)
	{
		global $APPLICATION;

		$params = parent::prepareComponentParams($params);
		$params['SERVICE'] = trim($params['SERVICE']);

		if ($params['SERVICE'] !== '')
		{
			$params['BASE_URL'] = $APPLICATION->GetCurPageParam(
				http_build_query([ 'service' => $params['SERVICE'] ]),
				[ 'service' ]
			);

			$params['GRID_ID'] .= '_' . Market\Data\TextString::toUpper($params['SERVICE']);
		}

		return $params;
	}

	protected function resolveTitle()
	{
		if ($this->titleResolved) { return; }

		$this->titleResolved = true;
		$setTitleParameter = $this->getComponentParam('SET_TITLE');

		if ($setTitleParameter === 'N') { return; }

		$services = $this->getRepository()->getTradingServices();
		$service = reset($services);

		if ($service === false) { return; }

		$title = $service->getInfo()->getMessage('SERVICE_LIST');

		$this->setComponentParam('TITLE', $title);
	}

	public function getFields(array $select = [])
	{
		$this->resolveTitle();

		$commonFields = parent::getFields($select);
		$commonFields = $this->getRepository()->extendCommonFields($commonFields);
		$calculatedFields = $this->getCalculatedFields();

		$result = $commonFields + $calculatedFields;
		$result = $this->sortFields($result);

		return $result;
	}

	protected function sortFields(array $fields)
	{
		$order = array_flip($this->getComponentParam('LIST_FIELDS'));

		uasort($fields, static function($fieldA, $fieldB) use ($order) {
			$sortA = isset($order[$fieldA['FIELD_NAME']]) ? $order[$fieldA['FIELD_NAME']] : 500;
			$sortB = isset($order[$fieldB['FIELD_NAME']]) ? $order[$fieldB['FIELD_NAME']] : 500;

			if ($sortA === $sortB) { return 0; }

			return $sortA < $sortB ? -1 : 1;
		});

		return $fields;
	}

	protected function getCalculatedFields()
	{
		if ($this->calculatedFields === null)
		{
			$this->calculatedFields = $this->makeCalculatedFields();
		}

		return $this->calculatedFields;
	}

	protected function makeCalculatedFields()
	{
		$environment = TradingEntity\Manager::createEnvironment();
		$sites = $environment->getSite()->getVariants();
		$siteId = reset($sites);
		$leftFields = [
			'CAMPAIGN_ID' => true,
			'YANDEX_INCOMING_URL' => true,
		];
		$result = [];

		foreach ($this->getRepository()->getTradingServices() as $tradingService)
		{
			$fields = $tradingService->getOptions()->getFields($environment, $siteId);
			$fields = array_intersect_key($fields, $leftFields);

			if (empty($fields)) { continue; }

			foreach ($fields as $name => $field)
			{
				$field += [
					'MULTIPLE' => 'N',
					'EDIT_IN_LIST' => 'Y',
					'EDIT_FORM_LABEL' => $field['NAME'],
					'FIELD_NAME' => $name,
					'SETTINGS' => [],
					'FILTERABLE' => false,
				];

				if (!isset($field['USER_TYPE']) && isset($field['TYPE']))
				{
					$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($field['TYPE']);
				}

				$result[$name] = array_diff_key($field, [ 'VALUE' => true ]);
				unset($leftFields[$name]);
			}

			if (empty($leftFields)) { break; }
		}

		return $result;
	}

	public function getDefaultFilter()
	{
		$result = parent::getDefaultFilter();
		$serviceFilter = $this->getUiServiceFilter('TRADING_SERVICE', 'TRADING');

		if ($serviceFilter !== null)
		{
			$result[] = $serviceFilter;
		}

		return $result;
	}

	public function load(array $queryParameters = [])
	{
		list($commonParameters, $calculatedParameters) = $this->extractLoadCalculatedParameters($queryParameters);

		if (isset($commonParameters['select']))
		{
			unset($commonParameters['select']); // select all
		}

		$result = parent::load($commonParameters);
		$result = $this->loadCalculated($result, $calculatedParameters);

		return $result;
	}

	protected function loadCalculated($items, $parameters)
	{
		if (empty($parameters['select'])) { return $items; }

		$selectMap = array_flip($parameters['select']);

		foreach ($items as &$item)
		{
			$setup = new TradingSetup\Model($item);
			$values = $setup->getSettings()->getValues();

			if (isset($selectMap['YANDEX_INCOMING_URL']))
			{
				$incomingPath = $setup->getEnvironment()->getRoute()->getPublicPath($setup->wakeupService()->getCode(), $setup->getUrlId());
				$incomingVariables = array_filter([
					'protocol' => 'https',
					'host' => Market\Data\SiteDomain::getHost($setup->getSiteId()),
				]);

				$values['YANDEX_INCOMING_URL'] = Market\Utils\Url::absolutizePath($incomingPath, $incomingVariables);
			}

			$item += array_intersect_key($values, $selectMap);
		}
		unset($item);

		return $items;
	}

	public function filterActions($item, $actions)
	{
		foreach ($actions as $actionKey => $action)
		{
			if (!isset($action['TYPE'])) { continue; }

			$isValid = true;

			switch ($action['TYPE'])
			{
				case 'ACTIVATE':
					$isValid = ($item['ACTIVE'] === Market\Export\Promo\Table::BOOLEAN_N);
				break;

				case 'DEACTIVATE':
					$isValid = ($item['ACTIVE'] === Market\Export\Promo\Table::BOOLEAN_Y);
				break;
			}

			if (!$isValid)
			{
				unset($actions[$actionKey]);
			}
		}

		return $actions;
	}

	public function processAjaxAction($action, $data)
	{
		switch ($action)
		{
			case 'activate':
				$result = $this->processActivateAction($data);
			break;

			case 'deactivate':
				$result = $this->processDeactivateAction($data);
			break;

			case 'delete':
				$result = $this->processDeleteAction($data);
			break;

			default:
				$result = parent::processAjaxAction($action, $data);
			break;
		}

		return $result;
	}

	protected function processActivateAction($data)
	{
		$selectedIds = $this->getActionSelectedIds($data);

		foreach ($selectedIds as $id)
		{
			$this->activateItem($id);
		}

		return $selectedIds;
	}

	protected function processDeactivateAction($data)
	{
		$selectedIds = $this->getActionSelectedIds($data);

		foreach ($selectedIds as $id)
		{
			$this->deactivateItem($id);
		}

		return $selectedIds;
	}

	protected function processDeleteAction($data)
	{
		$selectedIds = $this->getActionSelectedIds($data);

		foreach ($selectedIds as $id)
		{
			$this->deactivateItem($id);
			$this->deleteItem($id);
		}

		return $selectedIds;
	}

	protected function activateItem($id)
	{
		$setup = $this->getRepository()->getTradingSetup($id);

		if (!$setup->isInstalled() && TradingService\Migration::isDeprecated($setup->getServiceCode())) { return; }

		$setup->install();
		$setup->activate();
	}

	protected function deactivateItem($id)
	{
		$setup = $this->getRepository()->getTradingSetup($id);

		$setup->deactivate();
		$setup->uninstall();
	}

	protected function isAllowBatch()
	{
		return false;
	}

	protected function getRepository()
	{
		if ($this->repository === null)
		{
			$this->repository = $this->makeRepository();
		}

		return $this->repository;
	}

	protected function makeRepository()
	{
		$uiService = $this->getUiService();
		$modelClass = $this->getModelClass();

		return new Repository($uiService, $modelClass);
	}
}