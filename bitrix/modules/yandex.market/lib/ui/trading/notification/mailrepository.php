<?php

namespace Yandex\Market\Ui\Trading\Notification;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service\Reference\Action\AbstractNotification as Notification;

class MailRepository extends AbstractRepository
{
	public function search(Notification $notification, $siteId)
	{
		$result = null;

		$query = Main\Mail\Internal\EventMessageTable::getList([
			'filter' => [
				'=EVENT_NAME' => $notification->getType('EMAIL'),
				'=LID' => $siteId,
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
		$language = Market\Data\Site::getLanguage($siteId);

		if ($this->searchType($notification, $language) === null)
		{
			$this->makeType($notification, $language);
		}

		return $this->makeMessage($notification, $siteId);
	}

	public function url($messageId)
	{
		return Market\Ui\Admin\Path::getPageUrl('message_edit', [
			'lang' => LANGUAGE_ID,
			'ID' => $messageId,
		]);
	}

	protected function searchType(Notification $notification, $language)
	{
		$result = null;
		$filter = [
			'EVENT_NAME' => $notification->getType('EMAIL'),
			'LID' => $language,
		];

		if (defined(Main\Mail\Internal\EventTypeTable::class . '::TYPE_EMAIL'))
		{
			$filter['EVENT_TYPE'] = Main\Mail\Internal\EventTypeTable::TYPE_EMAIL;
		}

		$query = \CEventType::GetList($filter);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	protected function makeType(Notification $notification, $language)
	{
		$typeId = \CEventType::Add([
			'EVENT_NAME' => $notification->getType('EMAIL'),
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
		$provider = new \CEventMessage();
		$messageId = $provider->Add([
			'ACTIVE' => 'Y',
			'LID' => $siteId,
			'EMAIL_FROM' => "#DEFAULT_EMAIL_FROM#",
			'EMAIL_TO' => "#EMAIL_TO#",
			'EVENT_NAME' => $notification->getType('EMAIL'),
			'SUBJECT' => $notification->getTemplateSubject('EMAIL'),
			'MESSAGE' => $notification->getTemplateBody('EMAIL'),
			'BODY_TYPE' => 'html',
		]);

		if ($messageId === false)
		{
			throw new Main\SystemException($provider->LAST_ERROR);
		}

		return $messageId;
	}
}