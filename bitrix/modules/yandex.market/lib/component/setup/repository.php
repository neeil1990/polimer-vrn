<?php

namespace Yandex\Market\Component\Setup;

use Yandex\Market\Ui;
use Yandex\Market\Export\Xml\Format as XmlFormat;

class Repository
{
	protected $uiService;
	protected $modelClass;

	public function __construct(Ui\Service\AbstractService $uiService, $modelClass)
	{
		$this->uiService = $uiService;
		$this->modelClass = $modelClass;
	}

	public function modifyExportServiceField($serviceField)
	{
		if (isset($serviceField['VALUES']))
		{
			$exportServices = $this->uiService->getExportServices();
			$exportServicesMap = array_flip($exportServices);
			$isInverted = $this->uiService->isInverted();

			foreach ($serviceField['VALUES'] as $optionKey => $option)
			{
				$isExists = isset($exportServicesMap[$option['ID']]);

				if ($isExists === $isInverted)
				{
					unset($serviceField['VALUES'][$optionKey]);
				}
			}
		}

		return $serviceField;
	}

	public function modifyExportFormatField($formatField, $serviceField)
	{
		if (isset($formatField['VALUES'], $serviceField['VALUES']))
		{
			$exportServices = array_column($serviceField['VALUES'], 'ID');
			$existsTypes = [];

			foreach ($exportServices as $service)
			{
				$types = XmlFormat\Manager::getTypeList($service);

				if ($types !== null)
				{
					$existsTypes += array_flip($types);
				}
			}

			foreach ($formatField['VALUES'] as $optionKey => $option)
			{
				if (!isset($existsTypes[$option['ID']]))
				{
					unset($formatField['VALUES'][$optionKey]);
				}
			}
		}

		return $formatField;
	}

	public function makeServiceDependFields($fields)
	{
		$services = isset($fields['EXPORT_SERVICE']['VALUES']) ? array_column($fields['EXPORT_SERVICE']['VALUES'], 'ID') : [];
		$supportedMap = $this->getServiceSupportedFieldsMap($services);

		foreach ($supportedMap as $fieldName => $supportedServices)
		{
			if (!isset($fields[$fieldName])) { continue; }

			$excludeServices = array_diff($services, $supportedServices);

			if (empty($supportedServices))
			{
				unset($fields[$fieldName]);
			}
			else if (!empty($excludeServices))
			{
				if (!isset($fields[$fieldName]['DEPEND']))
				{
					$fields[$fieldName]['DEPEND'] = [];
				}

				$fields[$fieldName]['DEPEND']['EXPORT_SERVICE'] = [
					'RULE' => 'EXCLUDE',
					'VALUE' => array_values($excludeServices),
				];
			}
		}

		return $fields;
	}

	protected function getServiceSupportedFieldsMap($services)
	{
		$configurableKeys = [
			'SHOP_DATA',
			'ENABLE_CPA',
			'ENABLE_AUTO_DISCOUNTS',
		];
		$configurableFields = array_fill_keys($configurableKeys, []);

		foreach ($services as $serviceName)
		{
			$types = XmlFormat\Manager::getTypeList($serviceName);
			$typeName = reset($types);
			$format = XmlFormat\Manager::getEntity($serviceName, $typeName);

			$formatFields = $format->getSupportedFields();
			$formatFieldsMap = array_flip($formatFields);

			foreach ($configurableFields as $fieldName => &$supported)
			{
				if (!isset($formatFieldsMap[$fieldName])) { continue; }

				$supported[] = $serviceName;
			}
			unset($supported);
		}

		return $configurableFields;
	}
}