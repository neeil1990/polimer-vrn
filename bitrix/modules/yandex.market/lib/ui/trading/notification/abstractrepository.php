<?php

namespace Yandex\Market\Ui\Trading\Notification;

use Yandex\Market\Trading\Service\Reference\Action\AbstractNotification as Notification;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class AbstractRepository
{
	abstract public function search(Notification $notification, $siteId);

	abstract public function make(Notification $notification, $siteId);

	abstract public function url($messageId);

	protected function compileTypeDescription(Notification $notification, array $variables)
	{
		$result = '';

		foreach ($variables as $key)
		{
			$title = $notification->getVariableTitle($key);

			$result .= sprintf('#%s# - %s', $key, $title);
			$result .= PHP_EOL;
		}

		return $result;
	}

	protected function getLanguage($siteId)
	{
		$environment = TradingEntity\Manager::createEnvironment();

		return  $environment->getSite()->getLanguage($siteId);
	}
}