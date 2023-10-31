<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $tag \Yandex\Market\Export\Xml\Tag\Base */
/** @var $attribute \Yandex\Market\Export\Xml\Attribute\Base */
/** @var $tagValues array */
/** @var $isTagPlaceholder bool */
/** @var $isAttributePlaceholder bool */
/** @var $sourcesList array */
/** @var $attributeInputName string */
/** @var $attributeValue array */
/** @var $attributeValueType string */
/** @var $attributeType string */
/** @var $tagId string|null */
/** @var $tagName string|null */
/** @var $attributeId string|null */
/** @var $attributeName string|null */
/** @var $isAttribute bool */
/** @var $isRequired bool */
/** @var $isDefined bool */

$selectedTypeId = null;
$attributeFullType = $tagId . ($isAttribute ? '.' . $attributeId : '');
$availableSources = $arResult['NODE_AVAILABLE_SOURCES'][$attributeFullType] ?: $arResult['SOURCE_TYPE_ENUM_MAP'];
$defaultSource = null;
$availableTypes = $arResult['TYPE_MAP'][$attributeValueType];
$attributeRecommendation = isset($arResult['RECOMMENDATION'][$attributeFullType]) ? $arResult['RECOMMENDATION'][$attributeFullType] : null;
$rowAttributes =
	'data-type="' . $attributeFullType . '"'
	. ' data-value-type="' . $attributeValueType . '"';

if ($isRequired)
{
	$rowAttributes .= ' data-required="true"';
}
else if (!$isAttribute || $attribute->isVisible())
{
	$rowAttributes .= ' data-persistent="true"';
}

if ($isAttribute && $attributeName === 'name')
{
	$rowAttributes .= ' data-copy-type="' . $tagId .'"';
}

if (isset($arResult['DEFAULT_SOURCES'][$attributeFullType]))
{
	$defaultSource = $arResult['DEFAULT_SOURCES'][$attributeFullType];
}
else
{
	reset($availableSources);
	$defaultSource = key($availableSources);
}

?>
<tr class="<?= $isAttributePlaceholder ? 'is--hidden' : ''; ?> js-param-node-collection__item" data-plugin="Field.Param.Node" <?= $rowAttributes; ?>>
	<td class="b-param-table__cell for--label">
		<input class="js-param-node__input" type="hidden" data-name="ID" <?php

			if (!$isTagPlaceholder && !$isAttributePlaceholder)
			{
				echo 'name="' . $attributeInputName . '[ID]' . '"';
				echo 'value="' . $attributeValue['ID'] . '"';
			}

		?> />
		<input class="js-param-node__input is--persistent" type="hidden" value="<?= htmlspecialcharsbx($attributeType); ?>" data-name="XML_TYPE" <?php

			if (!$isTagPlaceholder && !$isAttributePlaceholder)
			{
				echo 'name="' . $attributeInputName . '[XML_TYPE]' . '"';
			}

		?> />
		<input class="js-param-node__input is--persistent" type="hidden" value="<?= htmlspecialcharsbx($attributeId); ?>" data-name="XML_ATTRIBUTE_NAME" <?php

			if (!$isTagPlaceholder && !$isAttributePlaceholder)
			{
				echo 'name="' . $attributeInputName . '[XML_ATTRIBUTE_NAME]' . '"';
			}

		?> />
		<?php
		include __DIR__ . '/name.php';
		?>
	</td>
	<td class="b-param-table__cell width--param-source-cell">
		<?php
		if ($isDefined)
		{
			?>
			<input type="hidden" name="js-param-node__input" value="<?= isset($attributeValue['SOURCE_TYPE']) ? htmlspecialcharsbx($attributeValue['SOURCE_TYPE']) : ''; ?>" data-name="SOURCE_TYPE" <?php

				if (!$isTagPlaceholder && !$isAttributePlaceholder)
				{
					echo 'name="' . $attributeInputName . '[SOURCE_TYPE]' . '"';
				}

			?> />
			<?php
		}
		?>
		<select class="b-param-table__input js-param-node__source js-param-node__input" <?= $isDefined ? 'disabled' : ''; ?> data-name="SOURCE_TYPE" <?php

			if (!$isTagPlaceholder && !$isAttributePlaceholder)
			{
				echo 'name="' . $attributeInputName . '[SOURCE_TYPE]' . '"';
			}

		?>>
			<?php
			foreach ($arResult['SOURCE_TYPE_ENUM'] as $typeEnum)
			{
				if (!isset($availableSources[$typeEnum['ID']])) { continue; }

				$isDefault = ($typeEnum['ID'] === $defaultSource);
				$isSelected = (
					(!$isTagPlaceholder && !$isAttributePlaceholder && $typeEnum['ID'] === $attributeValue['SOURCE_TYPE'])
					|| ($isDefault && empty($attributeValue['SOURCE_TYPE']))
				);

				if ($isSelected)
				{
					$selectedTypeId = $typeEnum['ID'];
				}

				?>
				<option value="<?= $typeEnum['ID'] ?>" <?= $isSelected ? 'selected': ''; ?> <?= $isDefault ? 'data-default="true"' : ''; ?>><?= $typeEnum['VALUE']; ?></option>
				<?php
			}
			?>
		</select>
	</td>
	<td class="b-param-table__cell width--param-field-cell">
		<?php
		$fieldPartName = 'SOURCE_FIELD';
		$fieldInputName = $isTagPlaceholder || $isAttributePlaceholder ? null : ($attributeInputName . '[SOURCE_FIELD]');
		$sourceField = isset($attributeValue['SOURCE_FIELD']) ? $attributeValue['SOURCE_FIELD'] : null;

		include __DIR__ . '/field-control.php';
		?>
	</td>
	<td class="b-param-table__cell">
		<?php
		if ($isAttribute)
		{
			if (!$attribute->isRequired() && !$attribute->isVisible())
			{
				?>
				<button class="adm-btn js-param-node-collection__item-delete" type="button">-</button>
				<?php
			}
		}
		else // is tag value
		{
			if ($tag->isMultiple() || $tag->isUnion() || (!$tag->isRequired() && !$tag->isVisible()))
			{
				?>
				<button class="adm-btn js-param-tag-collection__item-delete level--<?= $tagLevel ?> <?= $tag->isRequired() && count($tagValues) <= 1 ? 'is--hidden' : ''; ?>" type="button">-</button>
				<?php
			}
		}
		?>
	</td>
</tr>