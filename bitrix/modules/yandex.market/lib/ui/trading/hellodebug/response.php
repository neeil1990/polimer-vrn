<?php

namespace Yandex\Market\Ui\Trading\HelloDebug;

use Bitrix\Main;
use Yandex\Market;

class Response
{
	const ERROR_MARKER = 'helloDebug';

	public static function send($reason, array $data = null, array $trace = null)
	{
		$response = array_filter([
			'error' => static::ERROR_MARKER,
			'reason' => $reason,
			'data' => $data,
			'trace' => !empty($trace) ? Market\Utils\Trace::formatTrace($trace) : null,
		]);

		\CHTTP::SetStatus('200 OK');
		Market\Utils\HttpResponse::sendJson($response);
	}
}