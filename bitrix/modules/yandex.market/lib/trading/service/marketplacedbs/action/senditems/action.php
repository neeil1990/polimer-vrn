<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendItems;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\MarketplaceDbs\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Marketplace\Action\SendItems\Action
{
	use Market\Reference\Concerns\HasMessage;

	public function __construct(
		TradingService\MarketplaceDbs\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		array $data
	)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	protected function createSendItemsRequest(array $items)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$reason = $this->request->getReason();
		$items = $this->sanitizeItems($items);
		$result = new TradingService\MarketplaceDbs\Api\SendItems\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($this->request->getOrderId());
		$result->setItems($items);
		$result->setReason($reason);

		return $result;
	}

	protected function logItems(array $items)
	{
		$logger = $this->provider->getLogger();
		$reason = $this->request->getReason();
		$message = static::getMessage('SEND_LOG', [
			'#EXTERNAL_ID#' => $this->request->getOrderId(),
			'#ITEMS_COUNT#' => $this->getItemsTotalCount($items),
			'#REASON#' => $this->provider->getItemsChangeReason()->getTitle($reason),
		]);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_ITEMS,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}
}