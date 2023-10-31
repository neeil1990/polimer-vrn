<?php

namespace Yandex\Market\Trading\Service\Turbo\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\OrderAccept\Action
{
	use TradingService\Common\Concerns\Action\HasUserRegistration;

	/** @var TradingService\Turbo\Provider $provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\User */
	protected $originalUser;

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function createUser()
	{
		if (!$this->needUserRegister())
		{
			parent::createUser();
		}
		else
		{
			$requestUser = $this->request->getOrder()->getUser();
			$requestUserData = $requestUser->getMeaningfulValues();
			$userRegistry = $this->environment->getUserRegistry();
			$user = $userRegistry->getUser($requestUserData);

			$this->configureUserRule($user);

			if ($user->isInstalled())
			{
				$this->attachUserToGroup($user);
				$this->user = $user;
			}
			else
			{
				parent::createUser();
				$this->originalUser = $user;
			}
		}
	}

	protected function installUser()
	{
		if ($this->originalUser !== null)
		{
			$user = $this->originalUser;
			$this->originalUser = null;

			$this->registerUser($user);
			$this->attachUserToGroup($user);
			$this->changeOrderUser($user);

			$this->user = $user;
		}
	}

	protected function fillRegion()
	{
		// disabled
	}

	protected function fillProperties()
	{
		$this->fillUtilProperties();
		$this->fillUserProperties();
	}

	protected function fillUserProperties()
	{
		$requestUser = $this->request->getOrder()->getUser();
		$userValues = $requestUser->getMeaningfulValues();

		$this->setMeaningfulPropertyValues($userValues);
	}

	protected function fillDelivery()
	{
		$deliveryId = $this->provider->getOptions()->getDeliveryId();

		if ($deliveryId !== '')
		{
			$requestOrder = $this->request->getOrder();
			$price = null;

			if ($requestOrder->hasDelivery())
			{
				$price = $requestOrder->getDelivery()->getPrice();
			}

			$this->order->createShipment($deliveryId, $price);
		}
	}

	protected function fillPaySystem()
	{
		$paySystemType = $this->request->getOrder()->getPaymentType();
		$paySystemId = (string)$this->provider->getOptions()->getPaySystemId($paySystemType);

		if ($paySystemId !== '')
		{
			$this->order->createPayment($paySystemId);
		}
	}

	protected function addOrder()
	{
		$this->installUser();
		parent::addOrder();
	}

	protected function collectDecline(Market\Result\Base $result)
	{
		$this->response->setField('order.accepted', false);
		$this->response->setField('order.reason', 'OUT_OF_DATE');

		foreach ($result->getErrors() as $error)
		{
			$errorResponse = [
				'code' => (string)$error->getCode(),
				'message' => (string)$error->getMessage(),
			];

			$this->response->pushField('order.errors', $errorResponse);
		}
	}
}