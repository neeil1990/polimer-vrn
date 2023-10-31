<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

/** @var $itemInputName string */
/** @var $itemValue array */
/** @var $isItemPlaceholder boolean */
/** @var $lang array */
/** @var $langStatic array */

$selectedSourceField = null;
$selectedSourceFieldType = null;
$isSelectedFieldAutocomplete = false;
$selectedCompare = null;
$selectedCompareDefined = null;
$isSelectedCompareMultiple = null;

?>
<input class="js-condition-item__input" type="hidden" data-name="ID" <?

	if (!$isItemPlaceholder)
	{
		echo ' name="' . $itemInputName . '[ID]"';
		echo ' value="' . $itemValue['ID'] . '"';
	}

?> />
<div class="b-filter-condition-fields">
	<div class="b-filter-condition-field type--field">
		<label class="b-filter-condition-field__label"><?= $langStatic['FIELD_FIELD']; ?></label>
		<?
		if (!empty($arParams['FIELD_ENUM']))
		{
			?>
			<div class="b-filter-condition-field__input adm-select-wrap">
				<select class="adm-select js-condition-item__input js-condition-item__field" data-name="FIELD" <?

					if (!$isItemPlaceholder)
					{
						echo ' name="' . $itemInputName . '[FIELD]"';
					}

				?>>
					<?
					$prevSource = null;
					$isSelectedFieldFound = false;

					foreach ($arParams['FIELD_ENUM'] as $field)
					{
						$fieldSource = (isset($field['SOURCE']) ? $field['SOURCE'] : null);

						if ($prevSource !== $fieldSource)
						{
							$prevSource = $fieldSource;

							if ($prevSource !== null) { echo '</optgroup>'; }

							if ($fieldSource !== null)
							{
								$source = $arParams['SOURCE_ENUM'][$field['SOURCE']];

								echo '<optgroup label="' . $source['VALUE'] . '">';
							}
						}

						$isSelectedField = (!$isItemPlaceholder && $itemValue['FIELD'] === $field['ID']);

						if (!isset($selectedSourceField) || $isSelectedField)
						{
							$selectedSourceField = $field['ID'];
							$selectedSourceFieldType = $field['TYPE'];
							$isSelectedFieldAutocomplete = !empty($field['AUTOCOMPLETE']);
							$isSelectedFieldFound = $isSelectedField;
						}

						?>
						<option value="<?= $field['ID']; ?>" <?= $isSelectedField ? 'selected' : ''; ?> data-type="<?= $field['TYPE']; ?>"><?= Market\Utils::htmlEscape($field['VALUE']); ?></option>
						<?
					}

					if ($prevSource !== null) { echo '</optgroup>'; }

					if (!$isSelectedFieldFound && !$isItemPlaceholder && !empty($itemValue['FIELD']))
					{
						?>
						<option value="<?= htmlspecialcharsbx($itemValue['FIELD']) ?>" selected><?= htmlspecialcharsbx($itemValue['FIELD']) ?></option>
						<?php
					}
					?>
				</select>
			</div>
			<?
		}
		else
		{
			?>
			<input class="b-filter-condition-field__input adm-input js-condition-item__input" data-name="FIELD" <?

				if (!$isItemPlaceholder)
				{
					echo ' name="' . $itemInputName . '[FIELD]"';
					echo ' value="' . htmlspecialcharsbx($itemValue['FIELD']) . '"';
				}

			?> />
			<?
		}
		?>
	</div>
	<div class="b-filter-condition-field type--compare">
		<label class="b-filter-condition-field__label"><?= $langStatic['FIELD_COMPARE']; ?></label>
		<?
		if (!empty($arParams['COMPARE_ENUM']))
		{
			?>
			<div class="b-filter-condition-field__input type--compare adm-select-wrap">
				<select class="adm-select js-condition-item__input js-condition-item__compare" data-name="COMPARE" <?

					if (!$isItemPlaceholder)
					{
						echo ' name="' . $itemInputName . '[COMPARE]"';
					}

				?>>
					<?
					foreach ($arParams['COMPARE_ENUM'] as $compareOption)
					{
						$isSelectedCompareOption = (!$isItemPlaceholder && $itemValue['COMPARE'] === $compareOption['ID']);
						$isActive = in_array($selectedSourceFieldType, $compareOption['TYPE_LIST']);

						if ($isSelectedCompareOption || ($selectedCompare === null && $isActive))
						{
							$selectedCompare = $compareOption['ID'];
							$selectedCompareDefined = isset($compareOption['DEFINED']) ? $compareOption['DEFINED'] : null;
							$isSelectedCompareMultiple = $compareOption['MULTIPLE'];
						}

						?>
						<option value="<?= $compareOption['ID']; ?>" <?= $isSelectedCompareOption ? 'selected' : ''; ?> <?= $isActive ? '' : 'disabled'; ?>><?= $compareOption['VALUE']; ?></option>
						<?
					}
					?>
				</select>
			</div>
			<?
		}
		else
		{
			?>
			<input class="b-filter-condition-field__input adm-input js-condition-item__input" data-name="COMPARE" <?

				if (!$isItemPlaceholder)
				{
					echo ' name="' . $itemInputName . '[COMPARE]"';
					echo ' value="' . $itemValue['COMPARE'] . '"';
				}

			?> />
			<?
		}
		?>
	</div>
	<div class="b-filter-condition-field type--value js-condition-item__value-cell <?= $selectedCompareDefined !== null ? 'visible--hidden' : ''; ?>">
		<label class="b-filter-condition-field__label"><?= $langStatic['FIELD_VALUE']; ?></label>
		<input class="js-condition-item__input-holder" type="hidden" data-name="VALUE" value="" <?

			if (!$isItemPlaceholder)
			{
				echo ' name="' . $itemInputName . '[VALUE]"';
			}

		?> />
		<?php
		$valueEnum = null;
		$isSelectedFieldDate = (
			$selectedSourceFieldType === Market\Export\Entity\Data::TYPE_DATETIME
			|| $selectedSourceFieldType === Market\Export\Entity\Data::TYPE_DATE
		);

		if (!empty($arParams['COMPARE_ENUM'][$selectedCompare]['ENUM']))
		{
			$valueEnum = $arParams['COMPARE_ENUM'][$selectedCompare]['ENUM'];
		}
		else if (!empty($arResult['DISPLAY_VALUE'][$selectedSourceField]))
		{
			$valueEnum = $arResult['DISPLAY_VALUE'][$selectedSourceField];
		}
		else if (!empty($arParams['VALUE_ENUM'][$selectedSourceField]))
		{
			$isSelectedFieldAutocomplete = false;
			$valueEnum = $arParams['VALUE_ENUM'][$selectedSourceField];
		}

		if ($selectedCompareDefined !== null)
		{
			?>
			<input class="js-condition-item__input js-condition-item__value" type="hidden" value="<?= $selectedCompareDefined; ?>" data-name="VALUE" <?

				if (!$isItemPlaceholder)
				{
					echo ' name="' . $itemInputName . '[VALUE]"';
				}

			?> />
			<?php
		}
		else if ($valueEnum !== null || $isSelectedCompareMultiple || $isSelectedFieldAutocomplete || $isSelectedFieldDate)
		{
			$isItemValueMultiple = is_array($itemValue['VALUE']);
			$itemValueList = (array)$itemValue['VALUE'];
			$conditionValueAttributes = [
				'class' => 'b-filter-condition-field__input js-condition-item__input js-condition-item__value js-plugin-delayed',
				'multiple' => $isSelectedCompareMultiple,
				'size' => 1,
				'data-plugin' => 'Ui.Input.FilterInput',
				'data-name' => 'VALUE',
			];

			if (!$isItemPlaceholder)
			{
				$conditionValueAttributes['name'] = $itemInputName . '[VALUE]' . ($isSelectedCompareMultiple ? '[]' : '');
			}

			if ($isSelectedFieldDate)
			{
				$conditionValueAttributes['data-type'] = 'date';
				$conditionValueAttributes['data-plugin'] = 'Ui.Input.FilterDate';

				if ($selectedSourceFieldType === Market\Export\Entity\Data::TYPE_DATETIME)
				{
					$conditionValueAttributes['data-type'] = 'dateTime';
					$conditionValueAttributes['data-time'] = 'true';
				}
			}
			else if ($isSelectedFieldAutocomplete)
			{
				$conditionValueAttributes['data-type'] = 'autocomplete';
				$conditionValueAttributes['data-autocomplete'] = 'true';
				$conditionValueAttributes['data-source-field'] = $itemValue['FIELD'];
				$conditionValueAttributes['data-source-compare'] = $itemValue['COMPARE'];
				$conditionValueAttributes['data-iblock-id'] = $arParams['CONTEXT']['IBLOCK_ID'];
			}

			?>
			<select <?= Market\Ui\UserField\Helper\Attributes::stringify($conditionValueAttributes); ?>>
				<?php
				$foundValues = [];

				if ($valueEnum !== null)
				{
					foreach ($valueEnum as $enum)
					{
						$isSelectedEnum = false;

						if (!$isItemPlaceholder)
						{
							$isSelectedEnum = ($isItemValueMultiple ? in_array($enum['ID'], $itemValue['VALUE']) : $itemValue['VALUE'] == $enum['ID']);
						}

						if ($isSelectedEnum)
						{
							$foundValues[$enum['ID']] = true;
						}

						?>
						<option value="<?= $enum['ID']; ?>" <?= $isSelectedEnum ? 'selected' : ''; ?>><?= Market\Utils::htmlEscape($enum['VALUE']); ?></option>
						<?php
					}
				}

				foreach ($itemValueList as $itemValue)
				{
					$itemValue = trim($itemValue);

					if ($itemValue !== '' && !isset($foundValues[$itemValue]))
					{
						?>
						<option value="<?= htmlspecialcharsbx($itemValue); ?>" selected><?= Market\Utils::htmlEscape($itemValue); ?></option>
						<?php
					}
				}
				?>
			</select>
			<?php
		}
		else
		{
			$itemValue = is_array($itemValue['VALUE']) ? reset($itemValue['VALUE']) : $itemValue['VALUE'];

			?>
			<input class="b-filter-condition-field__input adm-input js-condition-item__input js-condition-item__value" data-name="VALUE" <?

				if (!$isItemPlaceholder)
				{
					echo ' name="' . $itemInputName . '[VALUE]"';
					echo ' value="' . htmlspecialcharsbx($itemValue) . '"';
				}

			?> />
			<?
		}
		?>
	</div>
</div>
