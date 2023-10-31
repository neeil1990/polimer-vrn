<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

/** @var Market\Export\Xml\Tag\Base $tag */
/** @var string $tagInputName */
/** @var array $tagValue */
/** @var boolean $isTagPlaceholder */
/** @var int $attributeIndex */

$addAttributeList = [];
$hasActiveAddAttribute = false;

foreach ($tag->getAttributes() as $attribute)
{
	$isDefined = $attribute->isDefined();

	if ($isDefined && !$attribute->isVisible()) { continue; } /* предопределенный аттрибут */

	$attributeInputName = $tagInputName . '[PARAM_VALUE][' . $attributeIndex . ']';
	$attributeValue = null;
	$attributeId = $attribute->getId();
	$attributeName = $attribute->getName();
	$attributeType = Market\Export\ParamValue\Table::XML_TYPE_ATTRIBUTE;
	$attributeValueType = $attribute->getValueType();
	$isAttribute = true;
	$isRequired = $attribute->isRequired();
	$isAttributePlaceholder = false;

	if (!$isTagPlaceholder && !empty($tagValue['PARAM_VALUE']))
	{
		foreach ($tagValue['PARAM_VALUE'] as $paramValue)
		{
			if (
				$paramValue['XML_TYPE'] === $attributeType
				&& $paramValue['XML_ATTRIBUTE_NAME'] === $attributeId
			)
			{
				$attributeValue = $paramValue;
				break;
			}
		}
	}

	if ($attributeValue === null)
	{
		$attributeValue = [];
		$isAttributePlaceholder = (!$attribute->isRequired() && !$attribute->isVisible());
	}

	if ($isDefined)
	{
		$definedSource = $attribute->getDefinedSource();

		$attributeValue['SOURCE_TYPE'] = $definedSource['TYPE'];
		$attributeValue['SOURCE_FIELD'] = (
			(!empty($arResult['SOURCE_TYPE_ENUM'][$definedSource['TYPE']]['VARIABLE']))
				? $definedSource['VALUE']
				: $definedSource['FIELD']
		);
	}

	include __DIR__ . '/value.php';

	if (!$attribute->isRequired())
	{
		$addAttributeList[$attributeId] = $isAttributePlaceholder;

		if ($isAttributePlaceholder)
		{
			$hasActiveAddAttribute = true;
		}
	}

	if (!$isAttributePlaceholder)
	{
		$attributeIndex++;
	}
}

if (!empty($addAttributeList))
{
	?>
	<tr class="<?= $hasActiveAddAttribute ? '' : 'is--hidden'; ?> js-param-node-collection__item-add-holder">
		<td class="b-param-table__cell width--param-label">&nbsp;</td>
		<td class="b-param-table__cell" colspan="3">
			<span class="js-params--show-hidden-tags">
				<span class="b-link" tabindex="0"><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_ADD_ATTRIBUTE', [ '#TAG_NAME#' => $tagName ]) ?></span>
				<span class="js-params--hidden-tags">
					<?php
					foreach ($addAttributeList as $attributeId => $isActive)
					{
						?>
						<span
							class="<?= $isActive ? '' : 'is--hidden'; ?> js-param-node-collection__item-add"
							tabindex="0"
							data-type="<?= $tagId . '.' . $attributeId; ?>"
						><?= $attributeId; ?></span>
						<?php
					}
					?>
				</span>
			</span>
		</td>
	</tr>
	<?php
}