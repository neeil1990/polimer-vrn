<?php

namespace Yandex\Market\Api\OAuth2\AccessToken;

use Bitrix\Main;
use Yandex\Market;

class ExchangeCode
{
	public function run($data)
	{
		$result = new Main\Result();
		$steps = [
			'requestToken',
			'userInfo',
			'saveToken',
			'scheduleRefresh'
		];

		foreach ($steps as $step)
		{
			$stepResult = $this->executeStep($step, $data);

			if ($stepResult->isSuccess())
			{
				$stepData = $stepResult->getData();

				if (!empty($stepData))
				{
					$data = $stepData + $data;
				}
			}
			else
			{
				$result = $stepResult;
				break;
			}
		}

		if ($data !== null && $result->isSuccess())
		{
			$result->setData($data);
		}

		return $result;
	}

	protected function executeStep($step, $data)
	{
		switch ($step)
		{
			case 'requestToken':
				$result = $this->requestToken($data);
			break;

			case 'userInfo':
				$result = $this->userInfo($data);
			break;

			case 'saveToken':
				$result = $this->saveToken($data);
			break;

			case 'scheduleRefresh':
				$result = $this->scheduleRefresh($data);
			break;

			default:
				throw new Main\SystemException('unknown step');
			break;
		}

		return $result;
	}

	protected function requestToken($data)
	{
		$accessTokenRequest = new Market\Api\OAuth2\AccessToken\Request();
		$accessTokenRequest->setOauthClientId($data['CLIENT_ID']);
		$accessTokenRequest->setOauthClientPassword($data['CLIENT_PASSWORD']);
		$accessTokenRequest->setVerificationCode($data['CODE']);
		$result = $accessTokenRequest->send();

		if ($result->isSuccess())
		{
			/** @var Response $response */
			$response = $result->getResponse();

			$data['TOKEN_TYPE'] = $response->getTokenType();
			$data['ACCESS_TOKEN'] = $response->getAccessToken();
			$data['REFRESH_TOKEN'] = $response->getRefreshToken();
			$data['EXPIRES_AT'] = $response->getExpiresDate();

			$result->setData($data);
		}

		return $result;
	}

	protected function userInfo($data)
	{
		$request = new Market\Api\User\Info\Request();
		$request->setOauthToken($data['ACCESS_TOKEN']);

		$result = $request->send();

		if ($result->isSuccess())
		{
			/** @var Market\Api\User\Info\Response $response */
			$response = $result->getResponse();

			$data['USER_ID'] = $response->getId();
			$data['USER_LOGIN'] = $response->getLogin();

			$result->setData($data);
		}

		return $result;
	}

	protected function saveToken($data)
	{
		$result = new Main\Result();
		$tokenId = $this->getExistToken($data['CLIENT_ID'], $data['USER_ID']);
		$saveData = [
			'CLIENT_ID' => $data['CLIENT_ID'],
			'USER_ID' => $data['USER_ID'],
			'USER_LOGIN' => $data['USER_LOGIN'],
			'TOKEN_TYPE' => $data['TOKEN_TYPE'],
			'ACCESS_TOKEN' => $data['ACCESS_TOKEN'],
			'REFRESH_TOKEN' => $data['REFRESH_TOKEN'],
			'EXPIRES_AT' => $data['EXPIRES_AT'],
			'SCOPE' => $data['SCOPE'],
			'REFRESH_COUNT' => 0,
			'REFRESH_MESSAGE' => ''
		];

		if ($tokenId !== null)
		{
			$storageResult = Market\Api\OAuth2\Token\Table::update($tokenId, $saveData);
		}
		else
		{
			$storageResult = Market\Api\OAuth2\Token\Table::add($saveData);
		}

		if ($storageResult->isSuccess())
		{
			$result->setData([
				'ID' => $tokenId !== null ? $tokenId : $storageResult->getId()
			]);
		}
		else
		{
			$result->addErrors($storageResult->getErrors());
		}

		return $result;
	}

	protected function getExistToken($clientId, $userId)
	{
		$result = null;

		$query = Market\Api\OAuth2\Token\Table::getList([
			'filter' => [ '=CLIENT_ID' => $clientId, '=USER_ID' => $userId ],
			'select' => [ 'ID' ]
		]);

		if ($row = $query->fetch())
		{
			$result = $row['ID'];
		}

		return $result;
	}

	protected function scheduleRefresh($data)
	{
		$result = new Main\Result();
		$agentResult = Market\Api\OAuth2\RefreshToken\Agent::schedule();

		if (!$agentResult->isSuccess())
		{
			$result->addErrors($agentResult->getErrors());
		}

		return $result;
	}
}