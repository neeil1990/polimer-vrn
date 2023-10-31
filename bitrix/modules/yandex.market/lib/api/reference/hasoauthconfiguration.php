<?php

namespace Yandex\Market\Api\Reference;

use Yandex\Market;

interface HasOauthConfiguration
{
	/**
	 * @return string
	 */
	public function getCampaignId();

	/**
	 * @return string
	 */
	public function getOauthClientId();

	/**
	 * @return string
	 */
	public function getOauthClientPassword();

	/**
	 * @return Market\Api\OAuth2\Token\Model
	 */
	public function getOauthToken();
}