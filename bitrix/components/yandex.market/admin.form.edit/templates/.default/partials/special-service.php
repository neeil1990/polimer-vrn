<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */
/** @var $arResult array */

/** @noinspection SpellCheckingInspection */
$this->addExternalJs('/bitrix/js/yandex.market/ui/input/dependlist.js');

$serviceField = $component->getField('EXPORT_SERVICE');
$serviceList = array_column($serviceField['VALUES'], 'ID');
$typeList = [];
$selectedService = null;
$availableTypeCount = 0;

if (
	!empty($arResult['ITEM']['EXPORT_SERVICE'])
	&& !in_array($arResult['ITEM']['EXPORT_SERVICE'], $serviceList, true)
)
{
	$serviceList[] = $arResult['ITEM']['EXPORT_SERVICE'];
}

foreach ($serviceList as $serviceIndex => $service)
{
	// types

	$serviceTypeList = (array)Market\Export\Xml\Format\Manager::getTypeList($service);

	foreach ($serviceTypeList as $typeIndex => $type)
	{
		$format = Market\Export\Xml\Format\Manager::getEntity($service, $type);

		if (
			$format instanceof Market\Export\Xml\Format\Reference\FormatDeprecated
			&& !(
				$arResult['ITEM']['EXPORT_SERVICE'] === $service
				&& $arResult['ITEM']['EXPORT_FORMAT'] === $type
			)
		)
		{
			unset($serviceTypeList[$typeIndex]);
			continue;
		}

		if (!isset($typeList[$type]))
		{
			$typeList[$type] = [];
		}

		$typeList[$type][$service] = true;
	}

	if (empty($serviceTypeList))
	{
		unset($serviceList[$serviceIndex]);
		continue;
	}

	// selected

	if ($selectedService === null || $service === $arResult['ITEM']['EXPORT_SERVICE'])
	{
		$selectedService = $service;
		$availableTypeCount = count($serviceTypeList);
	}
}

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		$isHidden = false;

		if ($specialFieldKey === 'EXPORT_FORMAT')
		{
			$isHidden = ($availableTypeCount <= 1);
		}
		else if ($specialFieldKey === 'EXPORT_SERVICE')
		{
			$isHidden = (count($serviceList) <= 1);
		}

		?>
		<tr class="<?= $isHidden ? 'is--hidden' : '' ?> js-form-field">
			<td class="adm-detail-content-cell-l" width="40%" align="right" valign="middle">
				<?php
				include __DIR__ . '/field-title.php';
				?>
			</td>
			<td class="adm-detail-content-cell-r" width="60%"><?php

				switch ($specialFieldKey)
				{
					case 'EXPORT_SERVICE':
						?>
						<select name="<?= $field['FIELD_NAME'] ?>" id="FIELD_EXPORT_SERVICE">
							<?php
							foreach ($serviceList as $service)
							{
								$isSelected = ($service === $selectedService);

								?>
								<option value="<?= $service ?>" <?= $isSelected ? 'selected' : '' ?>><?= Market\Export\Xml\Format\Manager::getServiceTitle($service) ?: $service ?></option>
								<?php
							}
							?>
						</select>
						<?php
					break;

					case 'EXPORT_FORMAT':
						?>
						<select class="b-depend-select js-plugin" name="<?= $field['FIELD_NAME'] ?>" data-plugin="Ui.Input.DependList" data-depend-element="#FIELD_EXPORT_SERVICE">
							<?php
							foreach ($typeList as $type => $availableServices)
							{
								$isSelected = ($type === $arResult['ITEM']['EXPORT_FORMAT']);
								$isAvailable = isset($availableServices[$selectedService]);
								$availableServicesList = array_keys($availableServices);

								?>
								<option value="<?= $type ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isAvailable ? '' : 'disabled hidden' ?> data-available="<?= implode(',', $availableServicesList) ?>">
									<?= Market\Export\Xml\Format\Manager::getTypeTitle($type) ?>
								</option>
								<?php
							}
							?>
						</select>
						<?php
					break;
				}
			?></td>
		</tr>
		<?php
	}
}
