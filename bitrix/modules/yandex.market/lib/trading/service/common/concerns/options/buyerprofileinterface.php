<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

interface BuyerProfileInterface
{
	const BUYER_PROFILE_RULE_NEW = 'new';
	const BUYER_PROFILE_RULE_FIRST = 'first';
	const BUYER_PROFILE_RULE_MATCH_EMAIL = 'matchEmail';
	const BUYER_PROFILE_RULE_MATCH_PHONE = 'matchPhone';
	const BUYER_PROFILE_RULE_MATCH_NAME = 'matchName';
	const BUYER_PROFILE_RULE_MATCH_FULL = 'matchFull';

	public function getBuyerProfileRule();
}