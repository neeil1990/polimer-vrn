<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

/** @var $tag \Yandex\Market\Export\Xml\Tag\Base */
/** @var $isTagPlaceholder bool */
/** @var $tagValue array */
/** @var $tagName string */
/** @var $tagInputName string */
/** @var $this CBitrixComponentTemplate */

$context = (array)$arParams['CONTEXT'];
$settings = $tag->getSettingsDescription($context);

if (!empty($settings))
{
	$settingsLayout = 'default';

	if ($tagName === 'url')
	{
		$settingsLayout = 'utm';
	}

	switch ($settingsLayout)
	{
		case 'utm':
			$this->addExternalCss('/bitrix/css/yandex.market/ui/collapse.css');
			$this->addExternalJs('/bitrix/js/yandex.market/ui/collapse.js');

			$hasFilled = false;
			$utmHtmlId = 'ym-utm-group-' . $tagName . '-' . $this->randString(3);

			if (!empty($tagValue['SETTINGS']) && is_array($tagValue['SETTINGS']))
			{
				foreach ($tagValue['SETTINGS'] as $settingValue)
				{
					if (!empty($settingValue['FIELD']))
					{
						$hasFilled = true;
						break;
					}
				}
			}

			?>
			<tr>
				<td class="b-param-table__cell width--param-label"></td>
				<td class="b-param-table__cell" colspan="3">
					<a class="b-link target--inside <?= $hasFilled ? 'is--active' : ''; ?> js-plugin" href="#" data-plugin="Ui.Collapse" data-target-element="#<?= $utmHtmlId; ?>" data-alt="<?= $hasFilled ? $langStatic['SETTINGS_UTM_TOGGLE_FILL'] : $langStatic['SETTINGS_UTM_TOGGLE_ALT']; ?>">
						<?= $hasFilled ? $langStatic['SETTINGS_UTM_TOGGLE_ALT'] : $langStatic['SETTINGS_UTM_TOGGLE']; ?>
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<div class="b-collapse <?= $hasFilled ? 'is--active' : ''; ?>" id="<?= $utmHtmlId; ?>">
						<table class="b-param-table__row">
			<?
		break;

		default:
			?>
			<tr>
				<td class="b-param-table__cell width--param-label"></td>
				<td class="b-param-table__cell" colspan="3">
					<table>
			<?
		break;
	}

	foreach ($settings as $settingName => $setting)
	{
		$inputName = null;
		$inputValue = null;
		$settingFullName = '[SETTINGS][' . $settingName . ']';

		if (!$isTagPlaceholder)
		{
			$inputName = $tagInputName . $settingFullName;
			$inputValue = isset($tagValue['SETTINGS'][$settingName]) ? $tagValue['SETTINGS'][$settingName] : null;
		}

		// header

		switch ($settingsLayout)
		{
			case 'utm':
				?>
				<tr <?= $setting['TYPE'] === 'param' ? 'class="js-param-tag__child" data-plugin="Field.Param.Node" data-name="' . $settingFullName . '"' : ''; ?>>
					<td class="b-param-table__cell for--label"><?= $setting['TITLE']; ?>:</td>
					<?
					if ($setting['TYPE'] !== 'param')
					{
						?>
						<td class="b-param-table__cell" colspan="2">
						<?
					}
			break;

			default:
				?>
				<tr>
					<td class="b-param-table__setting-label">
						<?php
						if (isset($setting['DESCRIPTION']))
						{
							?><span class="b-icon icon--question indent--right b-tag-tooltip--holder">
								<span class="b-tag-tooltip--content"><?= $setting['DESCRIPTION']; ?></span>
							</span><?php
						}

						echo $setting['TITLE'] . ':';
						?>
					</td>
					<td class="b-param-table__setting-value">
				<?
			break;
		}

		// body

		switch ($setting['TYPE'])
		{
			case 'enumeration':
				?>
				<select class="js-param-tag__input" type="text" <?= ($inputName !== null ? 'name="' . $inputName . '"' : ''); ?> data-name="<?= $settingFullName; ?>">
					<?
					foreach ($setting['VALUES'] as $option)
					{
						?>
						<option value="<?= $option['ID'] ?>" <?= $option['ID'] == $inputValue ? 'selected' : ''; ?>><?= Market\Utils::htmlEscape($option['VALUE']); ?></option>
						<?
					}
					?>
				</select>
				<?
			break;

			case 'param':
				$attributeFullType = null;
				$availableTypes = null;
				$defaultSource = isset($setting['DEFAULT']['TYPE']) ? $setting['DEFAULT']['TYPE'] : Market\Export\Entity\Manager::TYPE_TEXT;
				$defaultField = isset($setting['DEFAULT']['FIELD']) ? $setting['DEFAULT']['FIELD'] : '';
				$sourceType = !empty($inputValue['TYPE']) ? $inputValue['TYPE'] : $defaultSource;
				$sourceField = !empty($inputValue['FIELD']) ? $inputValue['FIELD'] : $defaultField;
				$selectedTypeId = null;
				$fieldInputName = $inputName  !== null ? $inputName . '[FIELD]' : null;
				$fieldPartName = 'FIELD';
				$isDefined = false;
				$disabledTypes = [
					$arResult['RECOMMENDATION_TYPE'] => true,
				];

				if (!Market\Config::isExpertMode())
				{
					$disabledTypes[Market\Export\Entity\Manager::TYPE_TEMPLATE] = true;
				}

				if ($settingsLayout === 'utm')
				{
					?>
					<td class="b-param-table__cell width--param-source-cell">
					<?
				}
				else
				{
					?>
					<div class="js-param-tag__child" data-plugin="Field.Param.Node" data-name="<?= $settingFullName; ?>">
					<?
				}
				?>
					<select class="b-param-table__input js-param-node__source js-param-node__input" data-name="TYPE" <?

						if ($inputName !== null)
						{
							echo 'name="' . $inputName . '[TYPE]' . '"';
						}

					?>>
						<?
						foreach ($arResult['SOURCE_TYPE_ENUM'] as $typeEnum)
						{
							if (isset($disabledTypes[$typeEnum['ID']])) { continue; }

							$isSelected = ($typeEnum['ID'] === $sourceType);
							$isDefault = ($typeEnum['ID'] === $defaultSource);

							if ($isSelected || $selectedTypeId === null)
							{
								$selectedTypeId = $typeEnum['ID'];
							}

							?>
							<option value="<?= $typeEnum['ID'] ?>" <?= $isSelected ? 'selected': ''; ?> <?= $isDefault ? 'data-default="true"' : ''; ?>><?= Market\Utils::htmlEscape($typeEnum['VALUE']); ?></option>
							<?
						}
						?>
					</select>
					<?

				if ($settingsLayout === 'utm')
				{
					?>
					</td>
					<td class="b-param-table__cell width--param-field-cell">
					<?
				}

				include __DIR__ . '/field-control.php';

				if ($settingsLayout === 'utm')
				{
					?>
					</td>
					<?
				}
				else
				{
					?>
					</div>
					<?
				}

			break;

			case 'boolean':
				?>
				<label>
					<input class="adm-designed-checkbox js-param-tag__input" type="checkbox" value="1" <?= ($inputName !== null ? 'name="' . $inputName . '"' : ''); ?> <?= (string)$inputValue === '1' ? 'checked' : ''; ?> data-name="<?= $settingFullName; ?>" />
					<span class="adm-designed-checkbox-label"></span>
				</label>
				<?php
			break;

			default:
				?>
				<input class="js-param-tag__input" type="text" <?= ($inputName !== null ? 'name="' . $inputName . '"' : ''); ?> value="<?= htmlspecialcharsbx($inputValue); ?>" data-name="<?= $settingFullName; ?>" />
				<?
			break;
		}

		// footer

		switch ($settingsLayout)
		{
			case 'utm':
					if ($setting['TYPE'] !== 'param')
					{
						?>
						</td>
						<?
					}
					?>
					<td></td>
				</tr>
				<?

			break;

			default:
				?>
					</td>
				</tr>
				<?
			break;
		}
	}

	switch ($settingsLayout)
	{
		case 'utm':
			?>
						</table>
					</div>
				</td>
			</tr>
			<?
		break;

		default:
			?>
					</table>
				</td>
			</tr>
			<?
		break;
	}
}