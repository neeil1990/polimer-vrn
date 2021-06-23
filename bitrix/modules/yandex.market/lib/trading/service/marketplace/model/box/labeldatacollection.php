<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Box;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class LabelDataCollection extends Market\Api\Reference\Collection
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function fetch(Market\Api\Reference\HasOauthConfiguration $options, $orderId)
	{
		$request = new TradingService\Marketplace\Api\BoxLabels\Request();

		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setOrderId($orderId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_BOX_LABEL_DATA_COLLECTION_FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var $response TradingService\Marketplace\Api\BoxLabels\Response */
		$response = $sendResult->getResponse();

		return $response->getResult()->getParcelBoxLabels();
	}

	public static function getItemReference()
	{
		return LabelData::class;
	}
}