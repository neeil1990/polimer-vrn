<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class CompleteActivity extends TradingService\Reference\Action\FormActivity
{
	use Market\Reference\Concerns\HasMessage;

	protected $title;
	protected $payload;
	protected $parameters;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		$title,
		array $payload,
		array $parameters = []
	)
	{
		parent::__construct($provider, $environment);
		$this->title = $title;
		$this->payload = $payload;
		$this->parameters = $parameters;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function useGroup()
	{
		return isset($this->parameters['USE_GROUP']) ? $this->parameters['USE_GROUP'] : true;
	}

	public function getFilter()
	{
		return isset($this->parameters['FILTER']) ? $this->parameters['FILTER'] : null;
	}

	public function getFields()
	{
		return [
			'realDeliveryDate' => [
				'TYPE' => 'date',
				'NAME' => self::getMessage('REAL_DELIVERY_DATE'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function getEntityValues($entity)
	{
		/** @var Market\Api\Model\Order $entity */
		Market\Reference\Assert::typeOf($entity, Market\Api\Model\Order::class, 'entity');

		if (!$entity->hasDelivery()) { return []; }

		$dates = $entity->getDelivery()->getDates();

		if ($dates === null) { return []; }

		return [
			'realDeliveryDate' => $dates->getRealDeliveryDate() ?: $dates->getFrom() ?: $dates->getTo(),
		];
	}

	public function getPayload(array $values)
	{
		return $this->payload + $values;
	}
}