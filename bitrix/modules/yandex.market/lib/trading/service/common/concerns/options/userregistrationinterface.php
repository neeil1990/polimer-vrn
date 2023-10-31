<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

interface UserRegistrationInterface
{
	const USER_RULE_MATCH_ANY = 'matchAny';
	const USER_RULE_MATCH_EMAIL = 'matchEmail';
	const USER_RULE_MATCH_PHONE = 'matchPhone';
	const USER_RULE_MATCH_NAME = 'matchName';
	const USER_RULE_MATCH_ID = 'matchId';
	const USER_RULE_ANONYMOUS = 'anonymous';

	public function getUserRule();
}