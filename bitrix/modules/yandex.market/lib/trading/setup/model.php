<?php

namespace Yandex\Market\Trading\Setup;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Model extends Market\Reference\Storage\Model
{
	use Market\Reference\Concerns\HasLang;

	protected $environment;
	protected $service;
	protected $isServiceReady;
	protected $originalFields;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public static function loadByTradingInfo(array $tradingInfo)
	{
		if (isset($tradingInfo['SETUP_ID']))
		{
			$result = static::loadById($tradingInfo['SETUP_ID']);
		}
		else if (isset($tradingInfo['TRADING_PLATFORM_ID'], $tradingInfo['SITE_ID']))
		{
			$result = static::loadByExternalIdAndSite($tradingInfo['TRADING_PLATFORM_ID'], $tradingInfo['SITE_ID']);
		}
		else
		{
			throw new Main\ArgumentException('unknown trading info format');
		}

		return $result;
	}

	/**
	 * @param string $serviceCode
	 * @param string $siteId
	 * @param string|null $behaviorCode
	 *
	 * @return Model
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function loadByServiceAndSite($serviceCode, $siteId, $behaviorCode = null)
	{
		$list = static::loadList([
			'filter' => [
				'=TRADING_SERVICE' => $serviceCode,
				'=TRADING_BEHAVIOR' => $behaviorCode ?: TradingService\Manager::BEHAVIOR_DEFAULT,
				'=SITE_ID' => $siteId,
			],
			'order' => [ 'ID' => 'ASC' ], // compatibility
			'limit' => 1,
		]);

		if (empty($list))
		{
			$message = static::getLang('TRADING_SETUP_MODEL_NOT_FOUND');
			throw new Main\ObjectNotFoundException($message);
		}

		return reset($list);
	}

	public static function loadByServiceAndUrlId($serviceCode, $urlId, $behaviorCode = null)
	{
		$list = static::loadList([
			'filter' => [
				'=TRADING_SERVICE' => $serviceCode,
				'=TRADING_BEHAVIOR' => $behaviorCode ?: TradingService\Manager::BEHAVIOR_DEFAULT,
				'=CODE' => $urlId,
			],
			'limit' => 1,
		]);

		if (empty($list))
		{
			$message = static::getLang('TRADING_SETUP_MODEL_NOT_FOUND');
			throw new Main\ObjectNotFoundException($message);
		}

		return reset($list);
	}

	/**
	 * @param string $externalId
	 * @param string $siteId
	 *
	 * @return Model
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function loadByExternalIdAndSite($externalId, $siteId)
	{
		$list = static::loadList([
			'filter' => [
				'=EXTERNAL_ID' => $externalId,
				'=SITE_ID' => $siteId,
			],
			'order' => [ 'ID' => 'ASC' ], // compatibility
			'limit' => 1,
		]);

		if (empty($list))
		{
			$message = static::getLang('TRADING_SETUP_MODEL_NOT_FOUND');
			throw new Main\ObjectNotFoundException($message);
		}

		return reset($list);
	}

	public function isInstalled()
	{
		return ($this->getId() > 0);
	}

	public function install()
	{
		$this->fillName();

		$this->installService();
		$this->installPlatform();
		$this->installInternalRecord();
	}

	public function uninstall()
	{
		$this->uninstallService();
	}

	public function getDefaultName()
	{
		$service = $this->getService();

		return $this->combineName($service);
	}

	protected function fillName()
	{
		if ((string)$this->getField('NAME') === '')
		{
			$this->resetName();
		}
	}

	protected function resetName()
	{
		$title = $this->getDefaultName();

		$this->setField('NAME', $title);
	}

	protected function replaceName(TradingService\Reference\Provider $service)
	{
		$title = $this->combineName($service);

		$this->setField('NAME', $title);
	}

	protected function combineName(TradingService\Reference\Provider $service)
	{
		return sprintf('%s (%s)', $service->getInfo()->getTitle(), $this->getSiteId());
	}

	protected function installService()
	{
		$environment = $this->getEnvironment();
		$siteId = $this->getSiteId();

		$this->getService()->getInstaller()->install($environment, $siteId);
	}

	protected function uninstallService()
	{
		$environment = $this->getEnvironment();
		$siteId = $this->getSiteId();
		$context = [
			'SITE_USED' => Facade::hasActiveSetup($siteId, $this->getId()),
			'SERVICE_USED' => Facade::hasActiveSetupUsingServiceCode($this->getServiceCode(), $this->getId()),
			'PLATFORM_USED' => Facade::hasActiveSetupUsingExternalPlatform($this->getExternalId(), $this->getId()),
		];

		$this->getService()->getInstaller()->uninstall($environment, $siteId, $context);
	}

	protected function installPlatform()
	{
		$platform = $this->getPlatform();

		if (!$platform->isInstalled())
		{
			$info = $this->getService()->getInfo();
			$platform->install($info);
		}

		$this->setField('EXTERNAL_ID', $platform->getId());
	}

	protected function installInternalRecord()
	{
		$fields = $this->getFields();
		$primary = $this->getId();

		if ($primary > 0)
		{
			$dbResult = Table::update($primary, array_diff_key($fields, [ 'ID' => true, ]));
		}
		else
		{
			$dbResult = Table::add($fields);

			if ($dbResult->isSuccess())
			{
				$id = $dbResult->getId();
				$this->setField('ID', $id);
			}
		}

		Market\Result\Facade::handleException($dbResult);
	}

	public function rollback()
	{
		$this->rollbackInternalRecord();
	}

	protected function rollbackInternalRecord()
	{
		$originalFields = $this->getOriginalFields();
		$diffFields = array_diff($originalFields, $this->getFields());
		$primary = $this->getId();

		if ($primary > 0)
		{
			$dbResult = Table::update($primary, $diffFields);

			Market\Result\Facade::handleException($dbResult);
		}
	}

	protected function storeOriginalFields()
	{
		$this->originalFields = $this->getFields();
	}

	protected function getOriginalFields()
	{
		if ($this->originalFields === null)
		{
			throw new Main\SystemException('before need store original fields');
		}

		return $this->originalFields;
	}

	public function migrate(TradingService\Reference\Provider $service = null)
	{
		$this->storeOriginalFields();

		$this->migratePlatform($service);
		$this->migrateService($service);

		$this->migrateName($service);
		$this->migrateServiceCode($service);
		$this->migrateInternalRecord();
	}

	protected function migrateName(TradingService\Reference\Provider $service = null)
	{
		if ($service !== null)
		{
			$this->replaceName($service);
		}
		else
		{
			$this->resetName();
		}
	}

	protected function migrateServiceCode(TradingService\Reference\Provider $service = null)
	{
		if ($service !== null)
		{
			$this->setField('TRADING_SERVICE', $service->getServiceCode());
			$this->setField('TRADING_BEHAVIOR', $service->getBehaviorCode());
		}
	}

	protected function migrateInternalRecord()
	{
		$primary = $this->getId();
		$fields = [
			'NAME' => $this->getField('NAME'),
			'TRADING_SERVICE' => $this->getField('TRADING_SERVICE'),
			'TRADING_BEHAVIOR' => $this->getField('TRADING_BEHAVIOR'),
		];

		if ($primary > 0)
		{
			$dbResult = Table::update($primary, $fields);

			Market\Result\Facade::handleException($dbResult);
		}
	}

	protected function migrateService(TradingService\Reference\Provider $service = null)
	{
		$environment = $this->getEnvironment();
		$siteId = $this->getSiteId();

		$this->getService()->getInstaller()->migrate($environment, $siteId, $service);
	}

	protected function migratePlatform(TradingService\Reference\Provider $service = null)
	{
		$platform = $this->getPlatform();

		if (!$platform->isInstalled()) { return; }

		if ($service !== null)
		{
			$code = $service->getCode();
			$info = $service->getInfo();

			$platform->migrate($code);
			$platform->update($info);
		}
		else
		{
			$info = $this->getService()->getInfo();

			$platform->update($info);
		}
	}

	public function validate()
	{
		$this->validateInternalRecord();
	}

	protected function validateInternalRecord()
	{
		$query = Table::getById($this->getId());
		$row = $query->fetch();
		$keys = [
			'TRADING_SERVICE'
		];

		if (!$row)
		{
			$message = static::getLang('TRADING_SETUP_MODEL_RECORD_NOT_EXISTS');
			throw new Main\SystemException($message);
		}

		foreach ($keys as $key)
		{
			$expected = $this->getField($key);
			$stored = $row[$key];

			if ($expected !== $stored)
			{
				$message = static::getLang('TRADING_SETUP_MODEL_RECORD_VALUE_NOT_MATCH', [
					'#EXPECTED#' => $expected,
					'#STORED#' => $stored,
				]);

				throw new Main\DB\SqlException($message);
			}
		}
	}

	public function isActive()
	{
		return (string)$this->getField('ACTIVE') === Table::BOOLEAN_Y;
	}

	public function activate()
	{
		$this->activatePlatform();
		$this->updateActiveFlag(true);
	}

	public function deactivate()
	{
		$this->deactivatePlatform();
		$this->updateActiveFlag(false);
	}

	protected function activatePlatform()
	{
		$this->getPlatform()->activate();
	}

	protected function deactivatePlatform()
	{
		if (!Facade::hasActiveSetupUsingExternalPlatform($this->getExternalId(), $this->getId()))
		{
			$this->getPlatform()->deactivate();
		}
	}

	protected function updateActiveFlag($direction)
	{
		$value = $direction ? Table::BOOLEAN_Y : Table::BOOLEAN_N;
		$updateResult = Table::update($this->getId(), [ 'ACTIVE' => $value ]);

		Market\Result\Facade::handleException($updateResult);
		$this->setField('ACTIVE', $value);
	}

	public function getServiceCode()
	{
		return $this->getField('TRADING_SERVICE');
	}

	public function getBehaviorCode()
	{
		return $this->getField('TRADING_BEHAVIOR') ?: TradingService\Manager::BEHAVIOR_DEFAULT;
	}

	public function getSiteId()
	{
		return $this->getField('SITE_ID');
	}

	public function getExternalId()
	{
		return $this->getField('EXTERNAL_ID');
	}

	public function getUrlId()
	{
		return $this->getField('CODE');
	}

	public function getEnvironment()
	{
		if ($this->environment === null)
		{
			$this->environment = $this->loadEnvironment();
		}

		return $this->environment;
	}

	protected function loadEnvironment()
	{
		return Market\Trading\Entity\Manager::createEnvironment();
	}

	public function getPlatform()
	{
		$serviceCode = $this->getService()->getCode();
		$siteId = $this->getSiteId();
		$platformRegistry = $this->getEnvironment()->getPlatformRegistry();

		return $platformRegistry->getPlatform($serviceCode, $siteId);
	}

	public function wakeupService()
	{
		$service = $this->getService();

		if (!$this->isServiceReady)
		{
			$settingValues = $this->getSettingsValues();

			$service->getOptions()->setValues($settingValues);
			$service->wakeup();

			$this->isServiceReady = true;
		}

		return $service;
	}

	public function getService()
	{
		if ($this->service === null)
		{
			$this->service = $this->loadService();
		}

		return $this->service;
	}

	protected function loadService()
	{
		$serviceCode = $this->getServiceCode();
		$behaviorCode = $this->getBehaviorCode();

		return TradingService\Manager::createProvider($serviceCode, $behaviorCode);
	}

	/**
	 * @return Market\Trading\Settings\Collection
	 */
	public function getSettings()
	{
		return $this->getChildCollection('SETTINGS');
	}

	public function getSettingsValues()
	{
		return array_merge(
			$this->getSettings()->getValues(),
			[
				'SETUP_ID' => $this->getId(),
				'SITE_ID' => $this->getSiteId(),
				'PLATFORM_ID' => $this->getExternalId(),
				'URL_ID' => $this->getUrlId(),
			]
		);
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'SETTINGS':
				$result = Market\Trading\Settings\Collection::getClassName();
			break;
		}

		return $result;
	}
}