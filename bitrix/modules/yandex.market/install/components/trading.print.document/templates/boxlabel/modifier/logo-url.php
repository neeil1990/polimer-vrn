<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Yandex\Market;

if (empty($arParams['SERVICE_LOGO_SRC'])) { return; }

$logoSrc = $arParams['SERVICE_LOGO_SRC'];
$logoPath = Main\IO\Path::convertRelativeToAbsolute($logoSrc);
$logoFile = new Main\IO\File($logoPath);

if ($logoFile->isExists())
{
	$arResult['SERVICE_LOGO_URL'] =
		$logoSrc
		. (Market\Data\TextString::getPosition($logoSrc, '?') === false ? '?' : '&')
		. $logoFile->getModificationTime();
}
else
{
	$arResult['SERVICE_LOGO_URL'] = $logoSrc;
}