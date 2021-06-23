<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $tag \Yandex\Market\Export\Xml\Tag\Base */
/** @var $attribute \Yandex\Market\Export\Xml\Attribute\Base */
/** @var $isAttribute bool */
/** @var $isTagRowShown bool */

if ($isAttribute && $isTagRowShown)
{
	$attributeDescription = (string)$attribute->getDescription();

	if ($attributeDescription !== '')
	{
		?><span class="b-icon icon--question indent--right b-tag-tooltip--holder">
			<span class="b-tag-tooltip--content"><?= $attributeDescription; ?></span>
		</span><?php
	}

	echo $attribute->getName() . '=';
}
else
{
	$tagNameDisplay = htmlspecialcharsbx('<' . $tag->getName() . '>');
	$tagDescription = (string)$tag->getDescription();

	if ($tagDescription !== '')
	{
		?>
		<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
			<span class="b-tag-tooltip--content"><?= $tagDescription; ?></span>
		</span>
		<?
	}

	if ($tag->isRequired())
	{
		?>
		<strong><?= $tagNameDisplay; ?></strong>
		<?
	}
	else
	{
		echo $tagNameDisplay;
	}
}