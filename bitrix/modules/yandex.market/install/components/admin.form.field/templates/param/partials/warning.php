<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

/** @var $tag \Yandex\Market\Export\Xml\Tag\Base */
/** @var $tagId string */
/** @var $tagName string */
/** @var $tagValue array */

if (isset($arResult['SUPPORT_WARNINGS'][$tagId]))
{
	$hasTagValueWarning = !empty($tagValue['WARNING']);

	?>
	<tr class="b-param-table__warning <?= !$hasTagValueWarning ? 'is--empty' : ''; ?> js-param-tag__warning-wrap">
		<td class="b-param-table__cell width--param-label"></td>
		<td class="b-param-table__cell js-param-tag__warning-place" colspan="3"><?php
			if ($hasTagValueWarning)
			{
				?><div class="b-message type--warning">
					<span class="b-message__icon"></span>
					<?= $tagValue['WARNING'] ?>
				</div><?php
			}
		?></td>
	</tr>
	<?
}