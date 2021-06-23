<?php

namespace Yandex\Market\Ui\Trading\Concerns;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

/** @method string getServiceCode() */
trait HasHandleMigration
{
	protected function handleMigration(\Exception $exception)
	{
		global $APPLICATION;

		if (!($exception instanceof Main\ObjectNotFoundException)) { return false; }

		$serviceCode = $this->getServiceCode();

		if (!Market\Trading\Service\Migration::hasMigrated($serviceCode)) { return false; }

		$migratedCodes = Market\Trading\Service\Migration::getMigrated($serviceCode);
		$migratedCollection = Market\Trading\Setup\Collection::loadByService($migratedCodes);
		$migratedSetup = $migratedCollection->getActive();
		$result = false;

		if ($migratedSetup !== null)
		{
			$result = true;
			$setupUrl = Market\Ui\Admin\Path::getModuleUrl('trading_edit', [
				'lang' => LANGUAGE_ID,
				'service' => $serviceCode,
			]);
			$oldUrl = $APPLICATION->GetCurPageParam(
				http_build_query([ 'service' => $migratedSetup->getServiceCode() ]),
				[ 'service' ]
			);

			\CAdminMessage::ShowMessage([
				'TYPE' => 'ERROR',
				'MESSAGE' => Market\Config::getLang('UI_TRADING_CONCERNS_MIGRATION_SERVICE_NEED_MIGRATE'),
				'DETAILS' => Market\Config::getLang('UI_TRADING_CONCERNS_MIGRATION_SERVICE_NEED_MIGRATE_DETAILS', [
					'#URL#' => $setupUrl,
				]),
				'HTML' => true,
			]);

			echo sprintf(
				'<a class="adm-btn" href="%s">%s</a><br /><br />',
				$oldUrl,
				Market\Config::getLang('UI_TRADING_CONCERNS_MIGRATION_SERVICE_FALLBACK_DEPRECATED')
			);
		}

		return $result;
	}

	protected function handleDeprecated(\Exception $exception)
	{
		global $APPLICATION;

		if (!($exception instanceof Main\ObjectNotFoundException)) { return false; }

		$serviceCode = $this->getServiceCode();

		if (!Market\Trading\Service\Migration::isDeprecated($serviceCode)) { return false; }

		$migrationCode = Market\Trading\Service\Migration::getDeprecateUse($serviceCode);
		$migrationUrl = $APPLICATION->GetCurPageParam(http_build_query([ 'service' => $migrationCode ]), [ 'service' ]);

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => Market\Config::getLang('UI_TRADING_CONCERNS_MIGRATION_SERVICE_DEPRECATED'),
			'DETAILS' => Market\Config::getLang('UI_TRADING_CONCERNS_MIGRATION_SERVICE_DEPRECATED_DETAILS', [
				'#URL#' => $migrationUrl,
			]),
			'HTML' => true,
		]);

		return true;
	}
}