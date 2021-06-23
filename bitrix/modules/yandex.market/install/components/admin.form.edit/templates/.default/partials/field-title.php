<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $field array */

$fieldTitle = $component->getFieldTitle($field);
$isRequired = ($field['MANDATORY'] === 'Y');

if (!empty($field['HELP_MESSAGE']))
{
	?><span class="b-icon icon--question indent--right b-tag-tooltip--holder">
		<span class="b-tag-tooltip--content"><?= $field['HELP_MESSAGE']; ?></span>
	</span><?
}

if ($isRequired)
{
	?><strong><?= $fieldTitle; ?></strong><?
}
else
{
	echo $fieldTitle;
}