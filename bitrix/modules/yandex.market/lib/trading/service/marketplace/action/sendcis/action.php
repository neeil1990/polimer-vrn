<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendCis;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property TradingService\Marketplace\Provider $provider */
/** @property Request $request */
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_CIS;
	}

	public function process()
	{
		$this->sendCis();
		$this->logCis();
	}

	protected function sendCis()
	{
		$request = $this->buildRequest();
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$message = static::getLang('TRADING_ACTION_SEND_CIS_RESPONSE_FAIL', [
				'#MESSAGE#' => implode(PHP_EOL, $sendResult->getErrorMessages())
			]);
			throw new Market\Exceptions\Api\Request($message);
		}
	}

	protected function buildRequest()
	{
		$result = new TradingService\Marketplace\Api\SendCis\Request();
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();

		$result->setLogger($logger);
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setCampaignId($options->getCampaignId());
		$result->setOrderId($this->request->getOrderId());
		$result->setItems($this->request->getItems());

		return $result;
	}

	protected function logCis()
	{
		$logger = $this->provider->getLogger();
		$message = static::getLang('TRADING_ACTION_SEND_CIS_SEND_LOG', [
			'#CIS_COUNT#' => $this->getCisCount(),
		]);

		$logger->info($message, [
			'AUDIT' => $this->getAudit(),
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function getCisCount()
	{
		$result = 0;

		foreach ($this->request->getItems() as $item)
		{
			if (!isset($item['instances'])) { continue; }

			$result += count($item['instances']);
		}

		return $result;
	}
}