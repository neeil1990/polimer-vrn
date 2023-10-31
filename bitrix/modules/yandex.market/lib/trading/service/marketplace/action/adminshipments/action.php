<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminShipments;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;

	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var TradingService\Marketplace\Model\ShipmentCollection */
	protected $shipments;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(TradingService\Marketplace\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		$this->loadShipments();

		$this->collectShipments();
		$this->collectPager();
	}

	protected function loadShipments()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$parameters = $this->request->getParameters();

		$request = new TradingService\Marketplace\Api\Shipments\Request();
		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());

		$request->processParameters($parameters);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getMessage('API_SHIPMENTS_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var TradingService\Marketplace\Api\Shipments\Response $response */
		$response = $sendResult->getResponse();

		$this->shipments = $response->getShipmentCollection();
	}

	protected function collectShipments()
	{
		$rows = [];

		foreach ($this->shipments as $shipment)
		{
			$rows[] = $this->getShipmentRow($shipment);
		}

		$this->response->setField('shipments', $rows);
	}

	protected function getShipmentRow(TradingService\Marketplace\Model\Shipment $shipment)
	{
		$deliveryService = $shipment->getDeliveryService();
		$result = [
			'ID' => $shipment->getId(),
			'EXTERNAL_ID' => $shipment->getExternalId(),
			'SERVICE_URL' => $this->getServiceUrl($shipment),
			'DATE' => [
				'FROM' => $shipment->getPlanIntervalFrom(),
				'TO' => $shipment->getPlanIntervalTo(),
			],
			'SHIPMENT_TYPE' => $shipment->getShipmentType(),
			'STATUS' => $shipment->getStatus(),
			'STATUS_DESCRIPTION' => $shipment->getStatusDescription(),
			'DELIVERY_SERVICE' => null,
			'DRAFT_COUNT' => $shipment->getDraftCount(),
			'PLANNED_COUNT' => $shipment->getPlannedCount(),
			'FACT_COUNT' => $shipment->getFactCount(),
			'PRINT_READY' => $this->isPrintReady($shipment),
			'PROCESSING' => $this->isProcessing($shipment),
		];

		if ($deliveryService !== null)
		{
			$result['DELIVERY_SERVICE'] = sprintf(
				'[%s] %s',
				$deliveryService->getId(),
				$deliveryService->getName()
			);
		}

		return $result;
	}

	protected function getServiceUrl(TradingService\Marketplace\Model\Shipment $shipment)
	{
		$baseUrl = sprintf(
			'https://partner.market.yandex.ru/business/%s/orders',
			$this->provider->getOptions()->getBusinessId()
		);

		return $baseUrl . '?' . http_build_query([
			'tabId' => 'readyForShipment',
			'tabGroupId' => 'fbsAndExpress',
			'isFiltersInit' => 1,
			'activeShipmentId' => $shipment->getId(),
			'shipmentDateFrom' => $shipment->getPlanIntervalFrom()->format('Y-m-d'),
			'shipmentDateTo' => $shipment->getPlanIntervalTo()->format('Y-m-d'),
			'shipmentStatus' => 'ALL',
			'shipmentWarehouseTo' => 'ALL',
			'shipmentOrdersPage' => 1,
		]);
	}

	protected function isPrintReady(TradingService\Marketplace\Model\Shipment $shipment)
	{
		if ((int)$shipment->getDraftCount() === 0) { return false; }

		$status = $shipment->getStatus();

		return (
			Market\Data\TextString::getPosition($status, 'OUTBOUND_') === 0
			|| Market\Data\TextString::getPosition($status, 'MOVEMENT_') === 0
			|| Market\Data\TextString::getPosition($status, 'WAITING_') === 0
			|| $status === 'FINISHED'
		);
	}

	protected function isProcessing(TradingService\Marketplace\Model\Shipment $shipment)
	{
		$status = $shipment->getStatus();

		return (
			Market\Data\TextString::getPosition($status, 'OUTBOUND_') === 0
			|| Market\Data\TextString::getPosition($status, 'WAITING_') === 0
		);
	}

	protected function collectPager()
	{
		$paging = $this->shipments->getPaging();

		if ($paging !== null && $paging->hasNext())
		{
			$this->response->setField('nextPageToken', $paging->getNextPageToken());
			$this->response->setField('nextPage', $this->request->getPage() + 1);
		}
	}
}