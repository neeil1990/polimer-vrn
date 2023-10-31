<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var Yandex\Market\Components\AdminFormEdit $component */
/** @var array $fields */
/** @var array $arParams */

$productKeys = $arParams['PRODUCT_FILTER_FIELDS'];
$foundProducts = array_intersect($fields, $productKeys);
$fields = array_diff($fields, $productKeys);
$foundProducts = array_filter($foundProducts, static function($name) use ($component) {
	$field = $component->getField($name);
	return empty($field['DEPEND_HIDDEN']);
});

if (empty($foundProducts))
{
	include __DIR__ . '/tab-default.php';
}
else
{
	?>
	<tr>
		<td class="b-form-section-holder" colspan="2">
			<div class="b-form-section fill--primary position--top">
				<table class="adm-detail-content-table edit-table" width="100%">
					<?php
					include __DIR__ . '/tab-default.php';
					?>
				</table>
			</div>
		</td>
	</tr>
	<?php

	$specialFields = $foundProducts;

	include __DIR__ . '/special-product-filter.php';
}