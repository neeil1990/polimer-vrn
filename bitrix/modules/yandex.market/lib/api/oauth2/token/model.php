<?php
namespace Yandex\Market\Api\OAuth2\Token;

use Yandex\Market;
use Bitrix\Main;

class Model extends Market\Reference\Storage\Model
{
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public static function loadByScope($scope)
	{
		$tableClass = static::getDataClass();
		$query = $tableClass::getList($q = [
			'filter' => [
				'%SCOPE' => '/' . $scope . '/',
				'>EXPIRES_AT' => new Main\Type\DateTime(),
			],
		]);

		while ($itemData = $query->fetch())
		{
			return new static($itemData);
		}

		return null;
	}

	/** @deprecated */
	public function setToken(Main\Web\Uri $rsUrl)
	{
		$rsUrl->addParams([
			'oauth_token' => $this->getField('ACCESS_TOKEN'),
			'oauth_client_id' => '1a620730ccbd4893ad4615cf8c6025de',
		]);
	}

	public function getClientId()
	{
		return $this->getField('CLIENT_ID');
	}

	public function getLogin()
	{
		return $this->getField('LOGIN');
	}

	public function getAccessToken()
	{
		return $this->getField('ACCESS_TOKEN');
	}

	public function getRefreshToken()
	{
		return $this->getField('REFRESH_TOKEN');
	}

	public function getExpiresDate()
	{
		return $this->getField('EXPIRES_AT');
	}

	public function canRefresh()
	{
		return $this->getRefreshCount() <= Market\Api\OAuth2\RefreshToken\Agent::getRefreshLimit();
	}

	public function incrementRefreshCount()
	{
		$current = $this->getRefreshCount();

		$this->setField('REFRESH_COUNT', $current + 1);
	}

	public function getRefreshCount()
	{
		return (int)$this->getField('REFRESH_COUNT');
	}

	public function getRefreshMessage()
	{
		return (string)$this->getField('REFRESH_MESSAGE');
	}
}
