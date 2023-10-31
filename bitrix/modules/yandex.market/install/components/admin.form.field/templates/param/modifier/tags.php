<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main;

$context = $arParams['CONTEXT'];
$arResult['TAGS'] = [];
$arResult['DOCUMENTATION_LINK'] = null;

try
{
	$typeTitle = Market\Export\Xml\Format\Manager::getTypeTitle($context['EXPORT_FORMAT']);
	$format = Market\Export\Xml\Format\Manager::getEntity(
		$context['EXPORT_SERVICE'],
		$context['EXPORT_FORMAT']
	);

	$arResult['DOCUMENTATION_LINK'] = $format->getDocumentationLink();
	$arResult['DOCUMENTATION_BETA'] = (Market\Data\TextString::getPosition($typeTitle, '(beta)') !== false);

	$root = $format->getOffer();
	$root->tune($context);

	$queue = [
		$root->getId() => $root,
	];

	while ($tag = reset($queue))
	{
		$tagId = key($queue);

		if (!$tag->isDefined())
		{
			$arResult['TAGS'][$tagId] = $tag;
		}

		foreach ($tag->getChildren() as $child)
		{
			$childId = ($root === $tag ? '' : $tagId . '.') . $child->getId();

			$queue[$childId] = $child;
		}

		array_shift($queue);
	}
}
catch (Main\SystemException $exception)
{
	$arResult['ERRORS'][] = $exception->getMessage();
}