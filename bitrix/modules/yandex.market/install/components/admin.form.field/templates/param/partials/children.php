<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Yandex\Market\Ui\UserField\Helper\Attributes;
use Bitrix\Main\Localization\Loc;

/** @var Market\Export\Xml\Tag\Base $tag */
/** @var string $tagInputName */
/** @var array $tagValue */
/** @var boolean $isTagPlaceholder */
/** @var string $parentBaseId */

if (!isset($previousContext)) { $previousContext = []; }

$previousContext[] = [
	$isParentPlaceholder,
	$parentBaseId,
	$parentInputName,
	isset($parentTag) ? $parentTag : null,
	isset($parentValue) ? $parentValue : null,
	isset($childrenAddList) ? $childrenAddList : null,
	$tagIndex,
	$tag,
	$tagValues,
	$tagValue
];

$isParentPlaceholder = $isTagPlaceholder;
$parentBaseId = $tagId . '.';
$parentInputName = $tagInputName . '[CHILDREN]';
$parentTag = $tag;
$parentValue = $tagValue;
$childrenAddList = [];
$tagIndex = 0;

++$tagLevel;

?>
<div class="js-param-tag__child" <?= Attributes::stringify([
	'data-plugin' => 'Field.Param.TagCollection',
	'data-name' => 'CHILDREN',
	'data-item-element' => '.js-param-tag-collection__item.level--' . $tagLevel,
	'data-item-add-holder-element' => '.js-param-tag-collection__item-add-holder.level--' . $tagLevel,
	'data-item-add-element' => '.js-param-tag-collection__item-add.level--' . $tagLevel,
	'data-item-delete-element' => '.js-param-tag-collection__item-delete.level--' . $tagLevel,
	'data-tag' => $tagId,
]) ?>>
	<?php
	foreach ($parentTag->getChildren() as $tag)
	{
		if ($tag->isDefined()) { continue; }

		$tagValues = [];

		if (!empty($parentValue['CHILDREN']))
		{
			foreach ($parentValue['CHILDREN'] as $childValue)
			{
				if ($tag->getId() === $childValue['XML_TAG'])
				{
					$tagValues[] = $childValue;
				}
			}
		}

		if ($tag->isMultiple() || $tag->isUnion())
		{
			$childrenAddList[$tag->getId()] = true;
		}
		else if (!$tag->isRequired() && !$tag->isVisible())
		{
			$childrenAddList[$tag->getId()] = empty($tagValues);
		}

		include __DIR__ . '/tag.php';
	}

	if (!empty($childrenAddList))
	{
		?>
		<table class="b-param-table__row <?= count(array_filter($childrenAddList)) > 0 ? '' : 'is--hidden'; ?> js-param-tag-collection__item-add-holder level--<?= $tagLevel ?>" data-type="<?= rtrim($parentBaseId, '.') ?>">
			<tr>
				<td class="b-param-table__cell width--param-label">&nbsp;</td>
				<td class="b-param-table__cell" colspan="3">
					<span class="js-params--show-hidden-tags">
						<span class="b-link" tabindex="0"><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_ADD_CHILD', [ '#TAG_NAME#' => $parentTag->getName() ]) ?></span>
						<span class="js-params--hidden-tags">
							<?php
							foreach ($childrenAddList as $childId => $isActive)
							{
								?>
								<span
									class="<?= $isActive ? '' : 'is--hidden'; ?> js-param-tag-collection__item-add level--<?= $tagLevel ?>"
									tabindex="0"
									data-type="<?= $parentBaseId . $childId; ?>"
								><?= $childId; ?></span>
								<?php
							}
							?>
						</span>
					</span>
				</td>
			</tr>
		</table>
		<?php
	}
	?>
</div>
<?php

// restore parent context

list(
	$isParentPlaceholder,
	$parentBaseId,
	$parentInputName,
	$parentTag,
	$parentValue,
	$childrenAddList,
	$tagIndex,
	$tag,
	$tagValues,
	$tagValue
) = array_pop($previousContext);

--$tagLevel;