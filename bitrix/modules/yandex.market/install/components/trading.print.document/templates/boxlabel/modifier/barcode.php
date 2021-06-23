<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Data\Barcode;

$barcodeFields = [
	'ORDER_ID',
	'ORDER_NUM',
	'FULFILMENT_ID',
];
$barcodeFormat = Barcode\Manager::createFormat(Barcode\Manager::FORMAT_CODE128);

foreach ($arResult['ITEMS'] as &$item)
{
	foreach ($barcodeFields as $fieldName)
	{
		if (!isset($item[$fieldName])) { continue; }

		$image = $barcodeFormat->getImage($item[$fieldName], 114, 3);

		ob_start();
		imagepng($image);
		imagedestroy($image);
		$contents = ob_get_clean();

		$item[$fieldName . '_BARCODE'] = 'data:image/png;base64,' . base64_encode($contents);
	}
}
unset($item);