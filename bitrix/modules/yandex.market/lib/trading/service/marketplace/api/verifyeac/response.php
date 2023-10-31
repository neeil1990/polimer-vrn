<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\VerifyEac;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Reference\Concerns;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Concerns\HasMessage;

	const VERIFICATION_RESULT_ACCEPTED = 'ACCEPTED';
	const VERIFICATION_RESULT_REJECTED = 'REJECTED';
	const VERIFICATION_RESULT_NEED_UPDATE = 'NEED_UPDATE';

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess()) { return $result; }

		$error = $this->validateVerificationResult();

		if ($error !== null)
		{
			$result = $result->addError($error);
		}

		return $result;
	}

	protected function validateVerificationResult()
	{
		$verificationResult = $this->getField('result.verificationResult');

		if ($verificationResult === static::VERIFICATION_RESULT_ACCEPTED) { return null; }

		if ($verificationResult === static::VERIFICATION_RESULT_REJECTED)
		{
			$result = new Main\Error(self::getMessage('VERIFICATION_REJECTED', [
				'#LEFT#' => $this->getField('result.attemptsLeft'),
			]));
		}
		else if ($verificationResult === static::VERIFICATION_RESULT_NEED_UPDATE)
		{
			$result = new Main\Error(self::getMessage('VERIFICATION_NEED_UPDATE'));
		}
		else
		{
			$result = new Main\Error(self::getMessage('VERIFICATION_UNKNOWN', [
				'#RESULT#' => $verificationResult,
			]));
		}

		return $result;
	}
}