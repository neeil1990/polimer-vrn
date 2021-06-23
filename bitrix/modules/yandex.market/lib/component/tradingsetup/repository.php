<?php

namespace Yandex\Market\Component\TradingSetup;

use Bitrix\Main;
use Yandex\Market\Config;
use Yandex\Market\Ui;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Setup as TradingSetup;

class Repository
{
	protected $uiService;
	protected $modelClass;

	public function __construct(Ui\Service\AbstractService $uiService, $modelClass)
	{
		$this->uiService = $uiService;
		$this->modelClass = $modelClass;
	}

	public function markNotEditableFields(array $fields)
	{
		foreach ($this->getNotEditableFields() as $key)
		{
			if (!isset($fields[$key])) { continue; }

			$fields[$key]['EDIT_IN_LIST'] = 'N';
		}

		return $fields;
	}

	public function unsetNotEditableValues(array $values)
	{
		$fields = $this->getNotEditableFields();

		return array_diff_key($values, array_flip($fields));
	}

	public function getNotEditableFields()
	{
		$result = [
			'TRADING_SERVICE',
			'TRADING_BEHAVIOR',
			'SITE_ID',
		];

		if (!Config::isExpertMode())
		{
			$result[] = 'CODE';
		}

		return $result;
	}

	public function extendCommonFields(array $fields)
	{
		foreach ($fields as $name => &$field)
		{
			if ($name === 'SITE_ID')
			{
				$field = $this->extendSiteField($field);
			}
			else if ($name === 'TRADING_SERVICE')
			{
				$field = $this->extendServiceField($field);
			}
			else if ($name === 'TRADING_BEHAVIOR')
			{
				$field = $this->extendBehaviorField($field);
			}
			else if ($name === 'ACTIVE')
			{
				$field = $this->extendActiveField($field);
			}
			else if ($name === 'CODE')
			{
				$field = $this->extendCodeField($field);
			}
			else if ($name === 'NAME')
			{
				$field = $this->extendNameField($field);
			}
		}
		unset($field);

		return $fields;
	}

	protected function extendSiteField(array $field)
	{
		$environment = TradingEntity\Manager::createEnvironment();
		$siteEntity = $environment->getSite();

		$field['USER_TYPE'] = Ui\UserField\Manager::getUserType('enumeration');
		$field['VALUES'] = [];

		foreach ($siteEntity->getVariants() as $siteId)
		{
			$title = $siteEntity->getTitle($siteId);

			$field['VALUES'][] = [
				'ID' => $siteId,
				'VALUE' => sprintf('[%s] %s', $siteId, $title),
			];
		}

		return $field;
	}

	protected function extendServiceField(array $field)
	{
		$codes = $this->uiService->getTradingServices();

		if (!isset($field['SETTINGS'])) { $field['SETTINGS'] = []; }

		$field['HIDDEN'] = 'Y';
		$field['SETTINGS']['DEFAULT_VALUE'] = reset($codes);

		return $field;
	}

	protected function extendBehaviorField(array $field)
	{
		$field['USER_TYPE'] = Ui\UserField\Manager::getUserType('enumeration');
		$field['VALUES'] = [];

		foreach ($this->getTradingServices() as $tradingService)
		{
			if (TradingService\Migration::isDeprecated($tradingService->getServiceCode())) { continue; }

			$title = $tradingService->getInfo()->getTitle('BEHAVIOR');
			$code = $tradingService->getBehaviorCode();

			$field['VALUES'][] = [
				'ID' => $code,
				'VALUE' => $title,
			];
		}

		unset($field['SETTINGS']['DEFAULT_VALUE']);

		return $field;
	}

	protected function extendActiveField(array $field)
	{
		if (!isset($field['SETTINGS']))
		{
			$field['SETTINGS'] = [];
		}

		$field['SETTINGS'] += [
			'USE_ICON' => 'Y',
		];

		return $field;
	}

	protected function extendCodeField(array $field)
	{
		$field['MANDATORY'] = 'N';

		return $field;
	}

	protected function extendNameField(array $field)
	{
		$field['MANDATORY'] = 'N';

		return $field;
	}

	/** @return TradingService\Reference\Provider[] */
	public function getTradingServices()
	{
		$result = [];

		foreach ($this->uiService->getTradingServices() as $serviceCode)
		{
			foreach (TradingService\Manager::getBehaviors($serviceCode) as $behaviorCode)
			{
				$result[] = TradingService\Manager::createProvider($serviceCode, $behaviorCode);
			}
		}

		return $result;
	}

	public function getTradingSetup($id)
	{
		$modelClass = $this->modelClass;
		$model = $modelClass::loadById($id);

		if (!($model instanceof TradingSetup\Model))
		{
			throw new Main\InvalidOperationException();
		}

		return $model;
	}
}