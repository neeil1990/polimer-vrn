<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $component \Yandex\Market\Components\AdminFormEdit */
/** @var $isActiveTab bool */

Loc::loadMessages(__FILE__);

// delivery section

$deliveryField = $component->getField('DELIVERY');
$salesNotesField = $component->getField('SALES_NOTES');

if ($deliveryField || $salesNotesField)
{
	?>
	<tr>
		<td class="b-form-section-holder" colspan="2">
			<div class="b-form-section fill--primary position--top">
				<span class="b-heading level--4">
					<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_SETUP_FILTER_DELIVERY_SECTION'); ?><?
					?><span class="b-icon icon--question indent--left b-tag-tooltip--holder">
						<span class="b-tag-tooltip--content b-tag-tooltip--content_right">
							<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_SETUP_FILTER_DELIVERY_SECTION_HELP'); ?>
						</span>
					</span>
				</span>
				<?
				include __DIR__ . '/special-delivery-and-sales-notes.php';
				?>
			</div>
		</td>
	</tr>
	<?
}

if (!empty($arResult['ITEM']['IBLOCK_LINK']))
{
	?>
	<tr>
		<td class="b-form-section-holder" colspan="2">
			<div class="b-form-section position--bottom">
				<?
				$isFirstIblockLink = true;

				foreach ($arResult['ITEM']['IBLOCK_LINK'] as $iblockLinkIndex => $iblockLink)
				{
					$iblockLinkBaseName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_';
					$iblock = (
						!empty($iblockLink['IBLOCK_ID']) && isset($arResult['IBLOCK_DATA'][$iblockLink['IBLOCK_ID']])
							? $arResult['IBLOCK_DATA'][$iblockLink['IBLOCK_ID']]
							: null
					);

					$deliveryField = $component->getField($iblockLinkBaseName . 'DELIVERY');
					$salesNotesField = $component->getField($iblockLinkBaseName . 'SALES_NOTES');
					$filterField = $component->getField($iblockLinkBaseName . 'FILTER');
					$exportAllField = $component->getField($iblockLinkBaseName . 'EXPORT_ALL');

					?>
					<input type="hidden" name="IBLOCK_LINK[<?= $iblockLinkIndex; ?>][ID]" value="<?= isset($iblockLink['ID']) ? $iblockLink['ID'] : ''; ?>" />
					<input type="hidden" name="IBLOCK_LINK[<?= $iblockLinkIndex; ?>][IBLOCK_ID]" value="<?= $iblockLink['IBLOCK_ID']; ?>" />
					<span class="b-heading level--3 <?= $isFirstIblockLink ? '' : 'spacing--3x2'; ?>">
						<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_IBLOCK_SECTION', [
							'#IBLOCK_NAME#' => $iblock !== null ? '&laquo;' . $iblock['NAME'] . '&raquo;' : '#' . $iblockLink['IBLOCK_ID']
						]); ?>
					</span>
					<div class="b-form-panel depth--1">
						<?
						if ($deliveryField || $salesNotesField)
						{
							?>
							<div class="b-form-panel__section fill--primary">
								<span class="b-heading level--4"><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_IBLOCK_LINK_DELIVERY_AND_NOTES_GROUP'); ?></span>
								<?
								include __DIR__ . '/special-delivery-and-sales-notes.php';
								?>
							</div>
							<?
						}

						if ($filterField || $exportAllField)
						{
							?>
							<div class="b-form-panel__section fill--secondary b-compensate compensate--1x1">
								<?
								if ($filterField)
								{
									$APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'filter', [
										'INPUT_NAME' => $filterField['FIELD_NAME'],
										'MULTIPLE' => 'Y',
										'VALUE' => $component->getFieldValue($filterField),
										'CONTEXT' => $iblockLink['CONTEXT'],
										'EXPORT_ADD_BUTTON' => $iblockLinkBaseName . 'FILTER_ADD',
										'EXPORT_LEFT_COUNT' => $exportAllField ? $iblockLinkBaseName . 'LEFT_COUNT' : null,
										'EXPORT_LEFT_MESSAGE' => $exportAllField ? $iblockLinkBaseName . 'LEFT_MESSAGE' : null,
										'REFRESH_COUNT_ON_LOAD' => $isActiveTab,
										'ALLOW_NAME' => 'Y',
										'ALLOW_SALES_NOTES' => 'Y',
										'ALLOW_DELIVERY_OPTIONS' => !empty($arResult['FORMAT_DATA']['SUPPORT_DELIVERY_OPTIONS']) ? 'Y' : 'N',
										'NEED_LEFT_COUNT' => 'Y'
									]);
								}

								if ($exportAllField)
								{
									?>
									<div class="spacing--1x1">
										<div class="b-grid spacing--1x1 b-compensate compensate--3x4">
											<div class="b-grid__item spacing--3x4">
												<?= $component->getFieldHtml($exportAllField); ?>
												<label for="<?= $exportAllField['FIELD_NAME']; ?>">
													<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_EXPORT_ALL_LABEL'); ?>
													<?
													$APPLICATION->ShowViewContent($iblockLinkBaseName . 'LEFT_COUNT');
													?>
												</label>
											</div>
											<div class="b-grid__item spacing--3x4">
												<?
												$APPLICATION->ShowViewContent($iblockLinkBaseName . 'LEFT_MESSAGE');
												?>
											</div>
										</div>
									</div>
									<?
								}

								if ($filterField)
								{
									$APPLICATION->ShowViewContent($iblockLinkBaseName . 'FILTER_ADD');
								}
								?>
							</div>
						</div>
						<?
					}

					$isFirstIblockLink = false;
				}
				?>
			</div>
		</td>
	</tr>
	<?
}