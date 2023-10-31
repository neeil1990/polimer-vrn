<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Config;
use Yandex\Market\Data\TextString;
use Yandex\Market\Psr;
use Yandex\Market\Utils;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Settings as TradingSettings;

class BusinessCampaigns
{
	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var bool */
	protected $loaded = false;
	/** @var Api\Partner\BusinessInfo\Response $response */
	protected $response;

	public function __construct(TradingService\Marketplace\Provider $provider)
	{
		$this->provider = $provider;
	}

	public function configured()
	{
		$stored = $this->stored();

		if (empty($stored)) { return []; }

		$external = $this->external();
		$configured = array_intersect_key($stored, $external);

		return array_values($configured);
	}

	protected function stored()
	{
		$result = [];

		$query = TradingSetup\Table::getList([
			'filter' => [
				'=TRADING_SERVICE' => $this->provider->getServiceCode(),
				'=ACTIVE' => TradingSetup\Table::BOOLEAN_Y,
			],
			'select' => [
				'ID',
				'NAME',
				'CAMPAIGN_ID_VALUE' => 'CAMPAIGN_ID.VALUE',
			],
			'runtime' => [
				new Main\Entity\ReferenceField('CAMPAIGN_ID', TradingSettings\Table::class, [
					'=ref.SETUP_ID' => 'this.ID',
					'=ref.NAME' => [ '?', 'CAMPAIGN_ID' ],
				], [
					'join_type' => 'inner',
				]),
			],
		]);

		while ($row = $query->fetch())
		{
			if ((string)$row['CAMPAIGN_ID_VALUE'] === '') { continue; }

			$result[$row['CAMPAIGN_ID_VALUE']] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['CAMPAIGN_ID_VALUE'], $row['NAME']),
			];
		}

		return $result;
	}

	public function external()
	{
		$response = $this->response();

		if ($response === null) { return []; }

		$result = [];

		/** @var Api\Partner\BusinessInfo\Model\Campaign $campaign */
		foreach ($response->getCampaigns() as $campaign)
		{
			$result[$campaign->getId()] = $campaign;
		}

		return $result;
	}

	protected function response()
	{
		if ($this->loaded === false)
		{
			$this->response = $this->load();
			$this->loaded = true;
		}

		return $this->response;
	}

	protected function load()
	{
		$cacheSign = $this->cacheSign();

		if ($cacheSign === null) { return null; }

		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = (int)Config::getOption('trading_marketplace_business_ttl', 900);
		$cacheDirectory = TextString::toLower(Config::getLangPrefix()) . '_marketplace_business';
		$result = null;

		// read cache

		if ($cache->read($cacheTtl, $cacheSign, $cacheDirectory))
		{
			$fields = $cache->get($cacheSign);

			if (is_array($fields))
			{
				$result = Api\Partner\BusinessInfo\Response::initialize($fields);
			}
		}

		// fetch

		if ($result === null)
		{
			$result = $this->fetch();

			if ($result !== null)
			{
				$cache->set($cacheSign, $result->getFields());
			}
		}

		return $result;
	}

	protected function cacheSign()
	{
		try
		{
			$result = $this->provider->getOptions()->getCampaignId();
		}
		catch (Main\SystemException $exception)
		{
			$result	= null;
		}

		return $result;
	}

	protected function fetch()
	{
		try
		{
			Utils\HttpConfiguration::stamp();
			Utils\HttpConfiguration::setGlobalTimeout(5);

			$request = new Api\Partner\BusinessInfo\Request();
			$options = $this->provider->getOptions();

			$request->setOauthClientId($options->getOauthClientId());
			$request->setOauthToken($options->getOauthToken()->getAccessToken());
			$request->setCampaignId($options->getCampaignId());

			$sendResult = $request->send();

			if (!$sendResult->isSuccess())
			{
				throw new Main\SystemException(implode(PHP_EOL, $sendResult->getErrorMessages()));
			}

			/** @var Api\Partner\BusinessInfo\Response $response */
			$response = $sendResult->getResponse();

			$result = $response;

			Utils\HttpConfiguration::restore();
		}
		catch (Main\SystemException $exception)
		{
			$result = null;

			Utils\HttpConfiguration::restore();
		}

		return $result;
	}
}