<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $tag \Yandex\Market\Export\Xml\Tag\Base */
/** @var $tagLevel int */
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
	echo $tagLevel > 0 ? str_repeat('....', $tagLevel) : '';
}
else
{
	$tagNameDisplay = htmlspecialcharsbx('<' . $tag->getName() . '>');
	$tagNameDisplay .= $tagLevel > 0 ? str_repeat('....', $tagLevel) : '';
	$tagDescription = (string)$tag->getDescription();

	if ($tagDescription !== '')
	{
		?>
		<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
			<span class="b-tag-tooltip--content"><?= $tagDescription; ?></span>
		</span>
		<?php
	}

	if ($tagLevel === 0  && $tag->isRequired())
	{
		?>
		<strong><?= $tagNameDisplay; ?></strong>
		<?php
	}
	else
	{
		echo $tagNameDisplay;
	}
}