<?php

namespace Yandex\Market\Api\Partner\Reference;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Reference\Response
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	protected function parseResponseError($error)
	{
		$result = parent::parseResponseError($error);

		return $this->humanizeResponseError($result);
	}

	protected function humanizeResponseError($error)
	{
		$result = $error;
		$messageVariants = [
			$error->getCode(),
			$error->getMessage(),
		];

		foreach ($messageVariants as $messageVariant)
		{
			$messageVariant = (string)$messageVariant;

			if ($messageVariant === '' || is_numeric($messageVariant)) { continue; }

			$langKey = Market\Data\TextString::toUpper($messageVariant);
			$langKey = preg_replace('/\W+/', '_', $langKey);
			$langKey = 'API_PARTNER_ERROR_' . $langKey;

			$newMessage = (string)static::getLang($langKey, null, '');

			if ($newMessage !== '')
			{
				$result = new Main\Error($newMessage, $error->getCode());
				break;
			}
		}

		return $result;
	}
}