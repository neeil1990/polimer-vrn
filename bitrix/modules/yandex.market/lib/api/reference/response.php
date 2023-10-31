<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

class Response extends Model
{
	use Market\Reference\Concerns\HasLang;

	protected $raw;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function initialize($fields, $relativePath = '')
	{
		$raw = null;

		if (!is_array($fields))
		{
			$raw = $fields;
			$fields = [];
		}

		$result = parent::initialize($fields, $relativePath);

		if ($raw !== null)
		{
			$result->setRaw($raw);
		}

		return $result;
	}

	public function validate()
	{
		$result = new Main\Result();

		if ($responseError = $this->validateErrorResponse())
		{
			$result->addError($responseError);
		}

		return $result;
	}

	protected function setRaw($contents)
	{
		$this->raw = $contents;
	}

	public function getRaw()
	{
		return $this->raw !== null ? $this->raw : $this->getFields();
	}

	protected function validateErrorResponse()
	{
		$result = null;

		if ($this->hasField('errors'))
		{
			$errors = (array)$this->getField('errors');
			$firstError = reset($errors);

			if ($firstError !== false)
			{
				$result = $this->parseResponseError($firstError);
			}
		}
		else if ($this->hasField('error'))
		{
			$error = $this->getField('error');

			$result = $this->parseResponseError($error);
		}

		return $result;
	}

	protected function parseResponseError($error)
	{
		$message = '';
		$code = null;

		if (!is_array($error))
		{
			$code = $error;
		}
		else
		{
			$message = isset($error['message']) ? $error['message'] : '';
			$code = isset($error['code']) ? $error['code'] : null;
		}

		if ($message === '')
		{
			$message = static::getLang('API_RESPONSE_UNDEFINED_ERROR', [
				'#CODE#' => $code
			]);
		}

		return new Main\Error($message, $code);
	}
}