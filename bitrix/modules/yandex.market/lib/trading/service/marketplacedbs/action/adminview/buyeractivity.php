<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\AdminView;

use Yandex\Market\Result;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Service as TradingService;

class BuyerActivity extends TradingService\Reference\Action\ViewActivity
	implements TradingService\Reference\Action\HasActivityEntityLoader
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getSort()
	{
		return 400;
	}

	public function getFilter()
	{
		return [
			'PROCESSING' => true,
		];
	}

	public function loadEntity($primary)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$request = new TradingService\MarketplaceDbs\Api\Buyer\Request();

		$request->setLogger($logger);
		$request->setCampaignId($options->getCampaignId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setOrderId($primary);

		$send = $request->send();

		Result\Facade::handleException($send);

		/** @var TradingService\MarketplaceDbs\Api\Buyer\Response $response */
		$response = $send->getResponse();

		return $response->getBuyer();
	}

	public function getEntityValues($entity)
	{
		/** @var TradingService\MarketplaceDbs\Model\Order\Buyer $entity */
		Assert::typeOf($entity, TradingService\MarketplaceDbs\Model\Order\Buyer::class, 'entity');

		return [
			'phone' => $entity->getPhone(),
			'firstName' => $entity->getFirstName(),
			'lastName' => $entity->getLastName(),
			'middleName' => $entity->getMiddleName(),
		];
	}

	public function getFields()
	{
		return [
			'phone' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('PHONE'),
			],
			'firstName' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('FIRST_NAME'),
			],
			'lastName' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('LAST_NAME'),
			],
			'middleName' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('MIDDLE_NAME'),
			],
		];
	}

	public function extendFields(array $fields, array $values = null)
	{
		return $values !== null
			? array_intersect_key($fields, array_filter($values))
			: $fields;
	}
}