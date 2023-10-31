<?php

namespace Yandex\Market\Ui\Trading\Notification;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service\Reference\Action\AbstractNotification as Notification;

class SmsRepository extends AbstractRepository
{
	public function isSupported()
	{
		return class_exists(Main\Sms\Event::class);
	}

	public function search(Notification $notification, $siteId)
	{
		$result = null;

		$query = Main\Sms\TemplateTable::getList([
			'filter' => [
				'=EVENT_NAME' => $notification->getType('SMS'),
				'=SITES.LID' => $siteId,
			],
			'select' => [ 'ID' ],
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	public function make(Notification $notification, $siteId)
	{
		$language = $this->getLanguage($siteId);

		if ($this->searchType($notification, $language) === null)
		{
			$this->makeType($notification, $language);
		}

		return $this->makeMessage($notification, $siteId);
	}

	protected function searchType(Notification $notification, $language)
	{
		$result = null;

		$query = \CEventType::GetList([
			'EVENT_NAME' => $notification->getType('SMS'),
			'EVENT_TYPE' => Main\Mail\Internal\EventTypeTable::TYPE_SMS,
			'LID' => $language,
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	protected function makeType(Notification $notification, $language)
	{
		$typeId = \CEventType::Add([
			'EVENT_NAME' => $notification->getType('SMS'),
			'EVENT_TYPE' => Main\Mail\Internal\EventTypeTable::TYPE_SMS,
			'LID' => $language,
			'NAME' => $notification->getTitle(),
			'DESCRIPTION' => $this->compileTypeDescription(
				$notification,
				$notification->getVariables()
			),
		]);

		if ($typeId === false)
		{
			throw Market\Exceptions\Facade::fromApplication();
		}

		return $typeId;
	}

	protected function makeMessage(Notification $notification, $siteId)
	{
		$site = Main\SiteTable::getEntity()->wakeUpObject($siteId);
		$template = Main\Sms\TemplateTable::getEntity()->createObject();

		$template->setEventName($notification->getType('SMS'));
		$template->setActive(true);
		$template->setSender('#DEFAULT_SENDER#');
		$template->setReceiver('+70000000000');
		$template->setMessage($notification->getTemplateBody('SMS'));
		$template->setLanguageId($this->getLanguage($siteId));
		$template->addToSites($site);

		$saveResult = $template->save();

		Market\Result\Facade::handleException($saveResult);

		return $template->getId();
	}

	public function url($messageId)
	{
		return Market\Ui\Admin\Path::getPageUrl('sms_template_edit', [
			'lang' => LANGUAGE_ID,
			'ID' => $messageId,
		]);
	}
}