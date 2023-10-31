<?php

namespace Yandex\Market\Api\Partner\File;

use Bitrix\Main;
use Yandex\Market;

class Facade
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function download(Market\Api\Reference\HasOauthConfiguration $options, $path, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = new Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setPath($path);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_PARTNER_FILE_DOWNLOAD_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var $response Response */
		$response = $sendResult->getResponse();

		return [ $response->getType(), $response->getContents() ];
	}
}