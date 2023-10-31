<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\FillOutlet;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;
	use TradingService\Common\Concerns\Action\HasMeaningfulProperties;
	use TradingService\Common\Concerns\Action\HasChangesTrait;
	use TradingService\MarketplaceDbs\Concerns\Action\HasAddress;

	/** @var Market\Api\Model\Outlet */
	protected $outlet;
	/** @var Request */
	protected $request;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			$this->loadOutlet();

			$this->fillRegistry();
			$this->fillProperties();

			if ($this->hasChanges())
			{
				$this->resolveOrderMarker(true);
				$this->updateOrder();
			}
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			$sendResult = new Main\Result();
			$sendResult->addError(new Main\Error(
				$exception->getMessage(),
				$exception->getCode()
			));

			$this->resolveOrderMarker(false, $sendResult);
			throw $exception;
		}
	}

	protected function loadOutlet()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$outletCode = $this->request->getOutletCode();
		$parameters = [
			'shop_outlet_code' => $outletCode,
		];

		$collection = Market\Api\Model\OutletFacade::loadList($options, $parameters, $logger);
		$outlet = $collection->offsetGet(0);

		if ($outlet === null)
		{
			throw new Market\Exceptions\Api\Request(self::getMessage('OUTLET_NOT_FOUND', [
				'#OUTLET_CODE#' => $outletCode,
			]));
		}

		$this->outlet = $outlet;
	}

	protected function fillRegistry()
	{
		$setupId = $this->provider->getOptions()->getSetupId();
		$outletType = TradingEntity\Registry::ENTITY_TYPE_OUTLET;
		$outletCode = $this->outlet->getShopOutletCode();
		$outletData = array_intersect_key($this->outlet->getFields(), [
			'name' => true,
			'phones' => true,
			'address' => true,
			'coords' => true,
		]);

		Market\Trading\State\EntityRegistry::store($setupId, $outletType, $outletCode, $outletData);
	}

	protected function fillProperties()
	{
		$address = TradingService\MarketplaceDbs\Model\Order\Delivery\Address::fromOutlet($this->outlet);
		$properties = $this->getAddressProperties($address);

		$this->setMeaningfulPropertyValues($properties);
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('FILL_OUTLET');
	}
}