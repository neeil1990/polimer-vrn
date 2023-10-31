<?php
namespace Yandex\Market\Trading\Facade;

use Yandex\Market;

class Business
{
	public static function synchronize()
	{
		$changed = false;

		$tradingSetups = Market\Trading\Setup\Model::loadList([
			'filter' => [ '=ACTIVE' => true ],
		]);

		foreach ($tradingSetups as $tradingSetup)
		{
			$service = $tradingSetup->wakeupService();

			if (!($service instanceof Market\Trading\Service\Marketplace\Provider)) { continue; }
			if ($service->getOptions()->getValue('BUSINESS_ID') !== null) { continue; }

			/** @var Market\Trading\Service\Marketplace\Command\LinkBusiness $command */
			$command = $service->getContainer()->get(Market\Trading\Service\Marketplace\Command\LinkBusiness::class, [
				'setupId' => $tradingSetup->getId(),
				'businessId' => $service->getOptions()->getValue('BUSINESS_ID'),
			]);
			$command->install();

			$changed = true;
		}

		return $changed;
	}
}