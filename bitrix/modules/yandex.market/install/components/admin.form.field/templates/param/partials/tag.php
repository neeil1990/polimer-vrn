<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Yandex\Market\Ui\UserField\Helper\Attributes;

/** @var \Yandex\Market\Export\Xml\Tag\Base $tag */

if (empty($tagValues))
{
	$tagValues[] = $tag->isRequired() || $tag->isVisible() ? [] : [ 'PLACEHOLDER' => true ];
}

foreach ($tagValues as $tagValue)
{
	$tagId = $parentBaseId . $tag->getId();
	$tagName = $tag->getName();
	$tagInputName = $parentInputName . '[' . $tagIndex . ']';
	$isTagPlaceholder = $isParentPlaceholder || !empty($tagValue['PLACEHOLDER']);
	$isTagRowShown = false;
	$isPersistent = ($tag->isRequired() || $tag->isVisible());
	$attributeIndex = 0;

	?>
	<div class="<?= $isTagPlaceholder && ($tagLevel === 0 || !$isPersistent) ? 'is--hidden' : ''; ?> js-param-tag-collection__item level--<?= $tagLevel ?>" <?= Attributes::stringify([
		'data-plugin' => 'Field.Param.Tag',
		'data-type' => $tagId,
		'data-multiple' => $tag->isMultiple() || $tag->isUnion() ? 'true' : false,
		'data-required' => $tag->isRequired() ? 'true' : false,
		'data-persistent' => $tagLevel > 0 && $isPersistent ? 'true' : false,
		'data-input-element' => sprintf('.js-param-tag__input[data-tag="%s"]', $tagId),
		'data-child-element' => sprintf('.js-param-tag__child[data-tag="%s"]', $tagId),
	]) ?>>
		<?php
		$tagValueDefined = [
			'[ID]' => $tagValue['ID'],
			'[XML_TAG]' => $tag->getId(),
		];

		if (!$tag->hasAttributes() && ($tag->hasChildren() || $tag->hasEmptyValue()))
		{
			$tagValueDefined['[PARAM_VALUE][0][ID]'] = null; // emulate PARAM_VALUE for tag without self value and attributes
		}

		foreach ($tagValueDefined as $name => $value)
		{
			$nameAttribute = $isTagPlaceholder ? '' : sprintf('name="%s%s"', $tagInputName, $name);
			$persistentClassName = ($name === '[XML_TAG]' ? ' is--persistent' : '');

			echo sprintf(
				'<input class="js-param-tag__input %s" type="hidden" %s value="%s" data-name="%s" data-tag="%s" />',
				$persistentClassName,
				$nameAttribute,
				$value,
				$name,
				$tagId
			);
		}
		?>
		<table class="b-param-table__row js-param-tag__child" data-plugin="Field.Param.NodeCollection" data-name="PARAM_VALUE" data-tag="<?= $tagId ?>">
			<?php
			if (!$tag->hasChildren() && !$tag->hasEmptyValue())
			{
				$isTagRowShown = true;
				$attributeInputName = $tagInputName . '[PARAM_VALUE][' . $attributeIndex . ']';
				$attributeValue = [];
				$attributeType = Market\Export\ParamValue\Table::XML_TYPE_VALUE;
				$attributeValueType = $tag->getValueType();
				$attributeId = null;
				$attributeName = null;
				$isAttribute = false;
				$isRequired = $tag->isRequired();
				$isDefined = $tag->isDefined();
				$isAttributePlaceholder = false;

				if (!empty($tagValue['PARAM_VALUE']))
				{
					foreach ($tagValue['PARAM_VALUE'] as $paramValue)
					{
						if ($paramValue['XML_TYPE'] === $attributeType)
						{
							$attributeValue = $paramValue;
							break;
						}
					}
				}

				include __DIR__ . '/value.php';

				$attributeIndex++;
			}
			else if ($tagLevel === 0 && $tagIndex === 0)
			{
				$isTagRowShown = true;
			}
			else
			{
				$isTagRowShown = true;
				$isAttribute = false;

				?>
				<tr>
					<td class="b-param-table__cell for--label"><?php
						include __DIR__ . '/name.php';
					?></td>
					<td class="b-param-table__cell width--param-source-cell">&nbsp;</td>
					<td class="b-param-table__cell width--param-field-cell">&nbsp;</td>
					<td class="b-param-table__cell"><?php
						if ($tag->isMultiple() || $tag->isUnion() || (!$tag->isRequired() && !$tag->isVisible()))
						{
							?>
							<button class="adm-btn js-param-tag-collection__item-delete level--<?= $tagLevel ?> <?= $tag->isRequired() && count($tagValues) <= 1 ? 'is--hidden' : ''; ?>" type="button">-</button>
							<?php
						}
					?></td>
				</tr>
				<?php
			}

			include __DIR__ . '/attributes.php';
			include __DIR__ . '/warning.php';
			include __DIR__ . '/settings.php';

			if (!$isTagPlaceholder)
			{
				$tagIndex++;
			}
			?>
		</table>
		<?php
		if (($tagLevel > 0 || $tagIndex !== 1) && $tag->hasChildren())
		{
			include __DIR__ . '/children.php';
		}
		?>
	</div>
	<?php
}