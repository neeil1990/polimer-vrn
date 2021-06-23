<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

/** @var $isDefined bool */
/** @var $selectedTypeId int */
/** @var $sourceField mixed */
/** @var $fieldInputName string|null */
/** @var $fieldPartName string */
/** @var $attributeFullType string */

$controlType = $arResult['SOURCE_TYPE_ENUM'][$selectedTypeId]['CONTROL'];
$sourceFieldAttributes = [
	'value' => $sourceField,
	'readonly' => (bool)$isDefined,
	'data-name' => $fieldPartName,
	'data-type' => $controlType,
];

if ($fieldInputName !== null)
{
	$sourceFieldAttributes['name'] = $fieldInputName;
}

if ($controlType === Market\Export\Entity\Manager::CONTROL_TEXT)
{
	?>
	<input
		class="b-param-table__input js-param-node__field js-param-node__input"
		type="text"
		<?= Market\Ui\UserField\Helper\Attributes::stringify($sourceFieldAttributes); ?>
	/>
	<?php
}
else if ($controlType === Market\Export\Entity\Manager::CONTROL_TEMPLATE)
{
	?>
	<div class="b-control-group js-param-node__field-wrap" <?= $arParams['ACTIVE_TAB'] ? 'data-plugin="Ui.Input.Template"' : ''; ?>>
		<input
			class="b-control-group__item pos--first b-param-table__input js-param-node__field js-param-node__input js-input-template__origin"
			type="text"
			<?= Market\Ui\UserField\Helper\Attributes::stringify($sourceFieldAttributes); ?>
		/>
		<button class="b-control-group__item pos--last width--by-content b-param-table__control adm-btn js-input-template__dropdown" type="button">...</button>
	</div>
	<?php
}
else if ($controlType === Market\Export\Entity\Manager::CONTROL_FORMULA)
{
	$formulaFunctionAttributes = array_merge($sourceFieldAttributes, [
		'value' => isset($sourceField['FUNCTION']) ? $sourceField['FUNCTION'] : null,
		'data-name' => '[' . $sourceFieldAttributes['data-name'] . '][FUNCTION]',
		'data-complex' => 'FUNCTION',
	]);
	$formulaPartsAttributes = array_merge($sourceFieldAttributes, [
		'value' => isset($sourceField['PARTS']) ? $sourceField['PARTS'] : null,
		'data-name' => '[' . $sourceFieldAttributes['data-name'] . '][PARTS]',
		'data-complex' => 'PARTS',
		'size' => 1,
	]);
	$formulaSelectedFunction = null;

	if (isset($sourceFieldAttributes['name']))
	{
		$formulaFunctionAttributes['name'] .= '[FUNCTION]';
		$formulaPartsAttributes['name'] .= '[PARTS]';
	}

	?>
	<div class="b-input-formula js-param-node__field-wrap" <?= $arParams['ACTIVE_TAB'] ? 'data-plugin="Ui.Input.Formula"' : ''; ?>>
		<select
			class="b-input-formula__function b-param-table__control js-param-node__input js-input-formula__function"
			<?= Market\Ui\UserField\Helper\Attributes::stringify(array_diff_key($formulaFunctionAttributes, [ 'value' => true ])); ?>
		>
			<?php
			if (isset($arResult['SOURCE_TYPE_ENUM'][$selectedTypeId]['FUNCTIONS']))
			{
				foreach ($arResult['SOURCE_TYPE_ENUM'][$selectedTypeId]['FUNCTIONS'] as $formulaFunction)
				{
					$isSelected = ($formulaFunction['ID'] === $formulaFunctionAttributes['value']);

					if ($isSelected || $formulaSelectedFunction === null)
					{
						$formulaSelectedFunction = $formulaFunction;
					}

					?>
					<option
						value="<?= htmlspecialcharsbx($formulaFunction['ID']); ?>"
						<?= $isSelected ? 'selected' : ''; ?>
						<?= !empty($formulaFunction['MULTIPLE']) ? 'data-multiple="true"' : ''; ?>
					><?= htmlspecialcharsbx($formulaFunction['VALUE']); ?></option>
					<?php
				}
			}
			?>
		</select>
		<div class="b-input-formula__parts-wrap">
			<?php
			if (!empty($formulaSelectedFunction['MULTIPLE']))
			{
				$formulaPartsAttributes['multiple'] = true;

				if (isset($formulaPartsAttributes['name']))
				{
					$formulaPartsAttributes['name'] .= '[]';
				}
			}
			?>
			<select
				class="b-input-formula__parts b-param-table__input js-param-node__field js-param-node__input js-input-formula__parts"
				<?= Market\Ui\UserField\Helper\Attributes::stringify(array_diff_key($formulaPartsAttributes, [ 'value' => true ]));?>
			>
				<?php
				foreach ((array)$formulaPartsAttributes['value'] as $formulaPart)
				{
					if ((string)$formulaPart === '') { continue; }

					if (isset($arResult['SOURCE_FIELD_ENUM'][$formulaPart]))
					{
						$formulaPartField = $arResult['SOURCE_FIELD_ENUM'][$formulaPart];
						$formulaPartSource = $arResult['SOURCE_TYPE_ENUM'][$formulaPartField['SOURCE']];
						$formulaPartOption = $formulaPartSource['VALUE'] . ': ' . $formulaPartField['VALUE'];

						?>
						<option value="<?= Market\Utils::htmlEscape($formulaPart); ?>" selected><?= Market\Utils::htmlEscape($formulaPartOption); ?></option>
						<?php
					}
					else
					{
						?>
						<option selected><?= Market\Utils::htmlEscape($formulaPart); ?></option>
						<?php
					}
				}
				?>
			</select>
		</div>
		<button class="b-input-formula__dropdown b-param-table__control adm-btn js-input-formula__dropdown" type="button">...</button>
	</div>
	<?php
}
else
{
	if ($isDefined)
	{
		$sourceFieldAttributes['disabled'] = true;
		$sourceFieldDefinedAttributes = array_intersect_key($sourceFieldAttributes, [
			'name' => true,
			'value' => true,
			'data-name' => true,
		]);

		?>
		<input
			type="hidden"
			name="js-param-node__input"
			<?= Market\Ui\UserField\Helper\Attributes::stringify($sourceFieldDefinedAttributes); ?>
		/>
		<?php
	}
	else if ($arParams['ACTIVE_TAB'])
	{
		$sourceFieldAttributes['data-plugin'] = 'Ui.Input.TagInput';
		$sourceFieldAttributes['data-width'] = '100%';
		$sourceFieldAttributes['data-tags'] = 'false';

		if ($arResult['MINIMAL_UI'])
		{
			$sourceFieldAttributes['data-lazy'] = 'true';
		}
	}
	?>
	<select
		class="b-param-table__input js-param-node__field js-param-node__input"
		<?= Market\Ui\UserField\Helper\Attributes::stringify(array_diff_key($sourceFieldAttributes, [ 'value' => true ])); ?>
	>
		<?php
		if (!$isRequired)
		{
			?>
			<option value=""><?= $lang['SELECT_PLACEHOLDER']; ?></option>
			<?
		}

		if ($selectedTypeId === $arResult['RECOMMENDATION_TYPE'])
		{
			foreach ($attributeRecommendation as $fieldEnum)
			{
				$isSelected = ($fieldInputName !== null && $fieldEnum['ID'] === $sourceFieldAttributes['value']);

				?>
				<option value="<?= $fieldEnum['ID'] ?>" <?= $isSelected ? 'selected': ''; ?>><?= Market\Utils::htmlEscape($fieldEnum['VALUE']); ?></option>
				<?
			}
		}
		else
		{
			foreach ($arResult['SOURCE_FIELD_ENUM'] as $fieldEnum)
			{
				if (
					$fieldEnum['SOURCE'] === $selectedTypeId
					&& ($availableTypes === null || isset($availableTypes[$fieldEnum['TYPE']]))
					&& (!isset($fieldEnum['TAG']) || in_array($attributeFullType, $fieldEnum['TAG'], true))
				)
				{
					$isSelected = ($fieldInputName !== null && $fieldEnum['ID'] === $sourceFieldAttributes['value']);

					?>
					<option value="<?= $fieldEnum['ID'] ?>" <?= $isSelected ? 'selected': ''; ?>><?= Market\Utils::htmlEscape($fieldEnum['VALUE']); ?></option>
					<?
				}
			}
		}
		?>
	</select>
	<?
}