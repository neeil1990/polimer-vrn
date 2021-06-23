<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

interface UserRegistrationInterface
{
	const USER_RULE_MATCH_ANY = 'matchAny';
	const USER_RULE_MATCH_EMAIL = 'matchEmail';
	const USER_RULE_MATCH_PHONE = 'matchPhone';
	const USER_RULE_ANONYMOUS = 'anonymous';

	public function getUserRule();
}