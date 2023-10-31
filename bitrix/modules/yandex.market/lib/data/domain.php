<?php

namespace Yandex\Market\Data;

class Domain
{
	public static function encode($domain)
	{
		$errors = [];
		$encodedDomain = \CBXPunycode::ToASCII($domain, $errors);

		return $encodedDomain !== false ? $encodedDomain : $domain;
	}

	public static function decode($encodedDomain)
	{
		$errors = [];
		$domain = \CBXPunycode::ToUnicode($encodedDomain, $errors);

		return $domain !== false ? $domain : $encodedDomain;
	}
}