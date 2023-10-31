<?php
namespace Yandex\Market\Api\Campaigns;

use Yandex\Market;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Api\Reference\HasOauthConfiguration;
use Yandex\Market\Psr\Log\LoggerInterface;
use Bitrix\Main;

class Facade
{
	use Concerns\HasMessage;

	public static function businessId(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$campaign = static::campaign($options, $logger);

		return $campaign->getBusiness()->getId();
	}

	public static function campaign(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$campaignId = $options->getCampaignId();
		$data = static::data($options, $logger);

		if (!isset($data[$campaignId]))
		{
			throw new Main\SystemException(self::getMessage('UNKNOWN_CAMPAIGN_FOR_TOKEN', [
				'#CAMPAIGN_ID#' => $campaignId,
				'#TOKEN#' => $options->getOauthToken()->getLogin(),
			]));
		}

		return new Model\Campaign($data[$campaignId]);
	}

	public static function businessCampaigns(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$all = static::campaigns($options, $logger);
		$campaign = $all->getItemByCampaignId($options->getCampaignId());

		if ($campaign === null)
		{
			throw new Main\SystemException(self::getMessage('UNKNOWN_CAMPAIGN_FOR_TOKEN', [
				'#CAMPAIGN_ID#' => $options->getCampaignId(),
				'#TOKEN#' => $options->getOauthToken()->getLogin(),
			]));
		}

		return $all->sameBusiness($campaign->getBusiness()->getId());
	}

	public static function campaigns(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$result = new Model\CampaignCollection();

		foreach (static::data($options, $logger) as $fields)
		{
			$campaign = new Model\Campaign($fields);

			$result->addItem($campaign);
			$campaign->setCollection($result);
		}

		return $result;
	}

	protected static function data(HasOauthConfiguration $options, LoggerInterface $logger = null)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheKey = 'CAMPAIGNS_' . $options->getOauthToken()->getId();

		if ($cache->read(86400, $cacheKey, Market\Trading\Setup\Table::getTableName()))
		{
			$result = $cache->get($cacheKey);
		}
		else
		{
			$page = 1;
			$result = [];

			do
			{
				$response = static::fetch($options, $page, $logger);

				if ($page > 100)
				{
					throw new Main\SystemException('infinite loop on fetch campaigns');
				}

				/** @var Model\Campaign $campaign */
				foreach ($response->getCampaigns() as $campaign)
				{
					$id = (int)$campaign->getId();

					$result[$id] = $campaign->getFields();
				}

				++$page;
			}
			while ($response->getPager()->hasNext());

			$cache->set($cacheKey, $result);
		}

		return $result;
	}

	protected static function fetch(HasOauthConfiguration $options, $page = 1, LoggerInterface $logger = null)
	{
		$request = new Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setPage($page);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = self::getMessage('FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var Response */
		return $sendResult->getResponse();
	}
}