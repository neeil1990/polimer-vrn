<?php

namespace Yandex\Market\Ui\Iblock;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class PropertyFeature extends Market\Reference\Event\Regular
{
	const FEATURE_ID_PREFIX = 'YAMARKET_';

	public static function getHandlers()
	{
		$result = [];

		if ((int)Main\ModuleManager::getVersion('main') >= 16)
		{
			$result[] = [
				'module' => 'iblock',
				'event' => Iblock\Model\PropertyFeature::class . '::OnPropertyFeatureBuildList',
				'method' => 'onPropertyFeatureBuildList',
			];
		}

		return $result;
	}

	public static function OnPropertyFeatureBuildList(Main\Event $event)
	{
		$features = [];
		$moduleName = Market\Config::getModuleName();

		foreach (static::getExportServiceTypes() as $type => $service)
		{
			$featureId = static::FEATURE_ID_PREFIX . Market\Data\TextString::toUpper($type);

			$features[] = [
				'MODULE_ID' => $moduleName,
				'FEATURE_ID' => $featureId,
				'FEATURE_NAME' => Market\Config::getLang('UI_IBLOCK_PROPERTY_FEATURE_NAME', [
					'#SERVICE#' => $service->getTitle('GENITIVE'),
				]),
			];
		}

		return new Main\EventResult(Main\EventResult::SUCCESS, $features);
	}

	protected static function getExportServiceTypes()
	{
		$result = [
			Market\Ui\Service\Manager::TYPE_COMMON => Market\Ui\Service\Manager::getCommonInstance(),
		];

		foreach (Market\Ui\Service\Manager::getTypes() as $type)
		{
			$service = Market\Ui\Service\Manager::getInstance($type);
			$exportServices = $service->getExportServices();

			if (!empty($exportServices))
			{
				$result[$type] = $service;
			}
		}

		return $result;
	}
}