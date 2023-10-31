<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class NotificationTemplate extends Market\Ui\Reference\Page
{
	public function processRequest()
	{
		$service = $this->getService();
		$repository = $this->getRepository();
		$notification = $this->getAction($service)->getNotification();
		$siteId = $this->getSiteId();
		$messageId = $repository->search($notification, $siteId);

		if ($messageId === null)
		{
			$messageId = $repository->make($notification, $siteId);
		}

		$url = $repository->url($messageId);

		LocalRedirect($url);
	}

	protected function getService()
	{
		$code = $this->getServiceCode();

		return TradingService\Manager::createProvider($code);
	}

	protected function getServiceCode()
	{
		$setup = $this->request->get('service');

		Market\Reference\Assert::notNull($setup, 'service');

		return $setup;
	}

	/**
	 * @param TradingService\Reference\Provider $service
	 *
	 * @return TradingService\Reference\Action\HasNotification
	 */
	protected function getAction(TradingService\Reference\Provider $service)
	{
		$path = $this->getActionPath();
		$environment = Market\Trading\Entity\Manager::createEnvironment();
		$action = $service->getRouter()->getHttpAction($path, $environment);

		Market\Reference\Assert::typeOf($action, TradingService\Reference\Action\HasNotification::class, 'action');

		return $action;
	}

	protected function getActionPath()
	{
		$path = $this->request->get('path');

		Market\Reference\Assert::notNull($path, 'path');

		return $path;
	}

	protected function getSiteId()
	{
		$path = $this->request->get('site');

		Market\Reference\Assert::notNull($path, 'site');

		return $path;
	}

	protected function getRepository()
	{
		$type = $this->request->get('type');

		Market\Reference\Assert::notNull($type, 'type');

		switch ($type)
		{
			case 'EMAIL':
				$result = new Notification\MailRepository();
			break;

			case 'SMS':
				$result = new Notification\SmsRepository();
			break;

			default:
				throw new Main\NotSupportedException('unknown notification type');
		}

		return $result;
	}
}