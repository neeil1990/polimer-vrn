<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Info
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	abstract public function getTitle($version = '');

	abstract public function getDescription();

	abstract public function getMessage($code, $replaces = null, $fallback = null);

	public function getProfileValues()
	{
		return [];
	}

	public function getUserGroupData()
	{
		return [];
	}

	public function getAnonymousUserData()
	{
		return [];
	}

	public function getCompanyData()
	{
		return [];
	}

	public function getContactData()
	{
		return [];
	}
}