<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Marketplace\Action\OrderStatus\Action
{
	use TradingService\Common\Concerns\Action\HasUserRegistration;
	use TradingService\MarketplaceDbs\Concerns\Action\HasDeliveryDates;
	use TradingService\MarketplaceDbs\Concerns\Action\HasAddress;

	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	public function __construct(TradingService\MarketplaceDbs\Provider $provider, TradingEntity\Reference\Environment $environment, Main\HttpRequest $request, Main\Server $server)
	{
		parent::__construct($provider, $environment, $request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function fillOrder()
	{
		parent::fillOrder();
		$this->fillUser();
	}

	protected function fillProperties()
	{
		$this->fillBuyerProperties();
		$this->fillAddressProperties();
		$this->fillDeliveryDatesProperties();
		$this->fillUtilProperties();
		$this->fillCancelReasonProperty();
	}

	protected function fillBuyerProperties()
	{
		$buyer = $this->request->getOrder()->getBuyer();

		if ($buyer !== null)
		{
			$values = $buyer->getMeaningfulValues();

			$this->setMeaningfulPropertyValues($values);
		}
	}

	protected function fillCancelReasonProperty()
	{
		$requestOrder = $this->request->getOrder();
		$status = $requestOrder->getStatus();
		$subStatus = $requestOrder->getSubStatus();

		if ($status !== TradingService\MarketplaceDbs\Status::STATUS_CANCELLED) { return; }

		$propertyId = (string)$this->provider->getOptions()->getProperty('REASON_CANCELED');

		if ($propertyId === '') { return; }

		$fillResult = $this->order->fillProperties([
			$propertyId => $subStatus,
		]);
		$fillData = $fillResult->getData();

		if (!empty($fillData['CHANGES']))
		{
			$this->pushChange('PROPERTIES', $fillData['CHANGES']);
		}
	}

	protected function fillUser()
	{
		$buyer = $this->request->getOrder()->getBuyer();

		if ($buyer !== null && $this->needUserRegister() && $this->isOrderUserAnonymous())
		{
			$buyerData = $buyer->getMeaningfulValues();
			$filteredData = $this->filterUserData($buyerData);
			$userRegistry = $this->environment->getUserRegistry();
			$user = $userRegistry->getUser($filteredData);

			if (!$user->isInstalled())
			{
				$this->registerUser($user);
			}

			$this->attachUserToGroup($user);
			$this->changeOrderUser($user);
			$this->pushChange('USER', $user->getId());
		}
	}

	protected function isOrderUserAnonymous()
	{
		$userId = $this->order->getUserId();

		return (
			$userId === null
			|| $userId === $this->getAnonymousUser()->getId()
		);
	}

	protected function getAnonymousUser()
	{
		$userRegistry = $this->environment->getUserRegistry();

		return $userRegistry->getAnonymousUser($this->provider->getServiceCode(), $this->getSiteId());
	}

	protected function updateOrder()
	{
		parent::updateOrder();
		$this->saveProfile();
	}

	protected function saveProfile()
	{
		if ($this->getChange('USER') === null) { return; }

		$values = $this->order->getPropertyValues();
		$values = array_filter($values);

		$command = new TradingService\Common\Command\SaveBuyerProfile(
			$this->provider,
			$this->environment,
			$this->order->getUserId(),
			$this->order->getPersonType(),
			$this->order->getProfileName(),
			$values
		);
		$command->execute();
	}

	protected function getStatusInSearchVariants()
	{
		$externalStatus = $this->request->getOrder()->getStatus();
		$paymentType = $this->request->getOrder()->getPaymentType();
		$servicePaySystem = $this->provider->getPaySystem();
		$result = [
			$externalStatus,
		];

		if ($servicePaySystem->isPrepaid($paymentType))
		{
			array_unshift($result, $externalStatus . '_PREPAID');
		}

		return $result;
	}
}