<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Bitrix\Main;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class AbstractNotification
{
	protected $provider;
	protected $environment;

	public function __construct(TradingService\Reference\Provider $provider, TradingEntity\Reference\Environment $environment)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	abstract public function getType($type);

	abstract public function getTitle();

	abstract public function getVariables();

	abstract public function getVariableTitle($code);

	abstract public function getTemplateSubject($type);

	abstract public function getTemplateBody($type);

	public function send(array $parameters)
	{
		$siteId = $this->getSiteId($parameters);
		$fields = $this->getFields($siteId, $parameters);

		$this->sendEmail($siteId, $fields);
		$this->sendSms($siteId, $fields);
		$this->sendPush($siteId, $fields, $parameters);
	}

	protected function getSiteId(array $parameters)
	{
		/** @var TradingService\Reference\Provider $provider */
		$provider = $this->extractParameter($parameters, 'PROVIDER', TradingService\Reference\Provider::class);

		return $provider->getOptions()->getSiteId();
	}

	abstract protected function getFields($siteId, array $parameters);

	protected function getCommonFields($siteId, array $parameters)
	{
		/** @var TradingService\Reference\Provider $provider */
		$provider = $this->extractParameter($parameters, 'PROVIDER', TradingService\Reference\Provider::class);
		$options = $provider->getOptions();

		return [
			'LANGUAGE_ID' => $this->getLanguage($siteId),
			'TRADING_SERVICE' => $provider->getServiceCode(),
			'TRADING_SETUP' => $options->getSetupId(),
			'EMAIL_TO' => Main\Config\Option::get('main', 'email_from'),
		];
	}

	protected function extractParameter(array $parameters, $key, $type = null)
	{
		$result = isset($parameters[$key]) ? $parameters[$key] : null;
		$argument = sprintf('parameters["%s"]', $key);

		Assert::notNull($result, $argument);

		if ($type !== null)
		{
			Assert::typeOf($result, $type, $argument);
		}

		return $result;
	}

	protected function sendEmail($siteId, array $fields)
	{
		$type = $this->getType('EMAIL');
		$language = $this->getLanguage($siteId);

		\CEvent::Send($type, $siteId, $fields, 'Y', '', [],  $language);
	}

	protected function sendSms($siteId, array $fields)
	{
		if (!class_exists(Main\Sms\Event::class)) { return; }

		$type = $this->getType('SMS');
		$language = $this->getLanguage($siteId);

		$sms = new Main\Sms\Event($type, $fields);
		$sms->setSite($siteId);
		$sms->setLanguage($language);
		$sms->send();
	}

	protected function sendPush($siteId, array $fields, array $parameters = [])
	{
		if (!class_exists(\CSaleMobileOrderPush::class)) { return; }
		if (!Main\Loader::includeModule('pull')) { return; }

		$orderId = $fields['INTERNAL_ID'];
		$users = \CSaleMobileOrderPush::getSubscribers('ORDER_STATUS_CHANGED', [
			'ORDER_ID' => $orderId,
		]);

		if (empty($users)) { return; }

		$title = $this->getTemplateSubject('SMS');
		$body = $this->getTemplateBody('SMS');
		$fields += \CEvent::GetSiteFieldsArray($siteId);
		$title = $this->compileTemplate($title, $fields);
		$body = $this->compileTemplate($body, $fields);
		$order = isset($parameters['ORDER']) ? $parameters['ORDER'] : $this->environment->getOrderRegistry()->loadOrder($orderId);
		$messages = [];

		foreach ($users as $userId)
		{
			if (!$order->hasAccess($userId, TradingEntity\Operation\Order::VIEW)) { continue; }

			$messages[] = [
				'USER_ID' => $userId,
				'TITLE' => $title,
				'MESSAGE' => $body,
				'APP_ID' => 'BitrixAdmin',
				'PARAMS' => 'sl_' . $orderId
			];
		}

		$pushManager = new \CPushManager();
		$pushManager->SendMessage($messages);
	}

	protected function compileTemplate($template, $fields)
	{
		$replaces = [];

		foreach ($fields as $key => $value)
		{
			$replaces['#' . $key . '#'] = $value;
		}

		return str_replace(
			array_keys($replaces),
			array_values($replaces),
			$template
		);
	}

	protected function getLanguage($siteId)
	{
		return $this->environment->getSite()->getLanguage($siteId);
	}
}