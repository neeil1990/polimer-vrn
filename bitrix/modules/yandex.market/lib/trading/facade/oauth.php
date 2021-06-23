<?php

namespace Yandex\Market\Trading\Facade;

use Yandex\Market;
use Bitrix\Main;

class Oauth
{
	public static function getConfiguration(Market\Api\OAuth2\Token\Model $token)
	{
		$result = null;

		$setupList = Market\Trading\Setup\Model::loadList([
			'filter' => [
				'=OAUTH_CLIENT_ID.VALUE' => $token->getClientId(),
				'=OAUTH_TOKEN.VALUE' => $token->getId(),
			],
			'runtime' => [
				new Main\Entity\ReferenceField('OAUTH_CLIENT_ID', Market\Trading\Settings\Table::class, [
					'=this.ID' => 'ref.SETUP_ID',
					'=ref.NAME' => [ '?', 'OAUTH_CLIENT_ID' ],
				]),
				new Main\Entity\ReferenceField('OAUTH_TOKEN', Market\Trading\Settings\Table::class, [
					'=this.ID' => 'ref.SETUP_ID',
					'=ref.NAME' => [ '?', 'OAUTH_TOKEN' ],
				]),
			]
		]);

		foreach ($setupList as $setup)
		{
			$options = $setup->getService()->getOptions();

			if ($options instanceof Market\Api\Reference\HasOauthConfiguration)
			{
				$setup->wakeupService();
				$result = $options;
				break;
			}
		}

		return $result;
	}
}