<?php
namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Data;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Utils\ArrayHelper;

class LinkBusiness
{
	protected static $knownBusinessInfo = [];

	protected $provider;
	protected $setupId;
	protected $businessId;

	public function __construct(TradingService\Marketplace\Provider $provider, $setupId, $businessId = null)
	{
		$this->provider = $provider;
		$this->setupId = (int)$setupId;
		$this->businessId = Data\Number::castInteger($businessId);
	}

	public function install()
	{
		try
		{
			$businessInfo = $this->businessInfo();

			$this->createBusiness($businessInfo);
			$this->saveLink($businessInfo->getId());
		}
		catch (Main\SystemException $exception)
		{
			$this->provider->getLogger()->warning($exception);
		}
	}

	public function uninstall()
	{
		if ($this->businessId === null) { return; }

		Trading\Business\Table::update($this->businessId, [
			'ACTIVE' => $this->someoneUsedBusiness(),
		]);
	}

	protected function businessInfo()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$campaignId = $options->getCampaignId();

		if (isset(static::$knownBusinessInfo[$campaignId]))
		{
			return static::$knownBusinessInfo[$campaignId];
		}

		$campaigns = Api\Campaigns\Facade::businessCampaigns($options, $logger);

		/** @var Api\Campaigns\Model\Campaign $campaign */
		foreach ($campaigns as $campaign)
		{
			static::$knownBusinessInfo[$campaign->getId()] = $campaign->getBusiness();
		}

		$campaign = $campaigns->getItemByCampaignId($campaignId);

		Assert::notNull($campaign, 'campaign');

		return $campaign->getBusiness();
	}

	protected function createBusiness(Api\Campaigns\Model\Business $businessInfo)
	{
		$exists = Trading\Business\Table::getRow([
			'filter' => [ '=ID' => $businessInfo->getId() ],
		]);

		if (!$exists)
		{
			Trading\Business\Table::add([
				'ID' => $businessInfo->getId(),
				'NAME' => $businessInfo->getName(),
				'ACTIVE' => Trading\Business\Table::BOOLEAN_Y,
			]);
		}
		else if (!$exists['ACTIVE'])
		{
			Trading\Business\Table::update($businessInfo->getId(), [
				'ACTIVE' => Trading\Business\Table::BOOLEAN_Y,
			]);
		}
	}

	protected function saveLink($businessId)
	{
		if ((int)$businessId === (int)$this->businessId) { return; }

		$primary = [
			'SETUP_ID' => $this->setupId,
			'NAME' => 'BUSINESS_ID',
		];
		$fields = [
			'VALUE' => $businessId,
		];

		$row = Trading\Settings\Table::getRow([
			'filter' => ArrayHelper::prefixKeys($primary, '='),
		]);

		if (!$row)
		{
			Trading\Settings\Table::add($primary + $fields);
		}
		else if ((int)$row['VALUE'] !== (int)$fields['VALUE'])
		{
			Trading\Settings\Table::update($primary, $fields);
		}
	}

	protected function someoneUsedBusiness()
	{
		Assert::notNull($this->businessId, 'businessId');

		$query = Trading\Setup\Table::getList([
			'select' => [ 'ID' ],
			'filter' => [
				'!=ID' => $this->setupId,
				'=ACTIVE' => Trading\Business\Table::BOOLEAN_Y,
				'=BUSINESS.ID' => $this->businessId,
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}
}