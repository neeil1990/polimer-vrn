<?php

namespace Yandex\Market\Component\Log;

use Bitrix\Main;
use Yandex\Market;

class GridList extends Market\Component\Data\GridList
{
	protected $uiService;

	public function prepareComponentParams($params)
	{
		global $APPLICATION;

		$params = parent::prepareComponentParams($params);
		$params['SERVICE'] = trim($params['SERVICE']);
		$params['SERVICE_BEHAVIOR'] = trim($params['SERVICE_BEHAVIOR']);
		$params['USE_SERVICE'] = isset($params['USE_SERVICE']) && $params['USE_SERVICE'] === 'Y';

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

	public function getDefaultFilter()
	{
		$result = parent::getDefaultFilter();
		$setupFilter = $this->getComponentParam('USE_SERVICE')
			? $this->getSetupIncludeFilter()
			: null;

		if ($setupFilter !== null)
		{
			$result[] = $setupFilter;
		}

		return $result;
	}

	protected function getSetupIncludeFilter()
	{
		$fields = $this->getComponentResult('FIELDS');
		$result = null;

		if (isset($fields['SETUP']['SETTINGS']['INCLUDE_VALUES']))
		{
			$setupField = $fields['SETUP'];
			$includeValues = $setupField['SETTINGS']['INCLUDE_VALUES'];
			$includeInverse = $setupField['SETTINGS']['INCLUDE_INVERSE'];

			if (!$includeInverse)
			{
				$result = [
					'=ENTITY_PARENT' => $fields['SETUP']['SETTINGS']['INCLUDE_VALUES'],
				];
			}
			else if (!empty($includeValues))
			{
				$result = [
					'!=ENTITY_PARENT' => $includeValues,
				];
			}
		}

		return $result;
	}

	public function getFields(array $select = [])
	{
		$result = parent::getFields($select);

		if (isset($result['SETUP']) && $this->getComponentParam('USE_SERVICE'))
		{
			$result['SETUP'] = $this->modifySetupField($result['SETUP']);
		}

		return $result;
	}

	protected function modifySetupField($field)
	{
		if (isset($field['SETTINGS']['DATA_CLASS']))
		{
			$uiService = $this->getUiService();
			$serviceBehavior = $this->getComponentParam('SERVICE_BEHAVIOR');
			$services = $uiService->getServices($serviceBehavior);

			$field['SETTINGS']['INCLUDE_VALUES'] = $this->getServiceSetupIds($field['SETTINGS']['DATA_CLASS'], $services);
			$field['SETTINGS']['INCLUDE_INVERSE'] = $uiService->isInverted();
		}

		return $field;
	}

	protected function getServiceSetupIds($dataClass, $serviceNames)
	{
		$dataClass = Main\Entity\Base::normalizeEntityClass($dataClass);
		$serviceFieldName = $this->getSetupDataClassServiceFieldName($dataClass);
		$result = [];

		$query = $dataClass::getList([
			'filter' => [ '=' . $serviceFieldName => $serviceNames ],
			'select' => [ 'ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row['ID'];
		}

		return $result;
	}

	protected function getSetupDataClassServiceFieldName($dataClass)
	{
		if (preg_match('/Yandex\\\Market\\\([^\\\]+)/i', $dataClass, $matches))
		{
			$result = Market\Data\TextString::toUpper($matches[1]) . '_SERVICE';
		}
		else
		{
			$result = 'SERVICE';
		}

		return $result;
	}

	protected function getUiService()
	{
		if ($this->uiService === null)
		{
			$this->uiService = $this->loadUiService();
		}

		return $this->uiService;
	}

	protected function loadUiService()
	{
		$serviceName = (string)$this->getComponentParam('SERVICE');

		if ($serviceName === '')
		{
			$result = Market\Ui\Service\Manager::getCommonInstance();
		}
		else
		{
			$result = Market\Ui\Service\Manager::getInstance($serviceName);
		}

		return $result;
	}
}