<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main;
use Yandex\Market;

/** @var \CBitrixComponentTemplate $this */
/** @var string $templateFolder */

$langPrefix = 'YANDEX_MARKET_T_TRADING_PRINT_PDF_BUNDLE_';

if (empty($arResult['DOWNLOAD_LIST']))
{
	ShowError(Loc::getMessage($langPrefix . 'EMPTY_DOWNLOAD_LIST'));
	return;
}

$polyfillName = Market\Ui\Extension::registerCompatible('main.polyfill.core'); // old bitrix fallback
$polyfill = CJSCore::GetHTML($polyfillName);

$assets = Main\Page\Asset::getInstance();

$assets->addString($polyfill, false, 'PRINT');
$assets->addJs($templateFolder . '/dist/pdfjs.js');
$assets->addJs($templateFolder . '/dist/printer.js');

?>
<div id="pdf-bundler">
	<div class="js-pdf-bundler__loading" style="display: none;"><?= Loc::getMessage($langPrefix . 'LOADING') ?> <span class="js-pdf-bundler__ready">0</span><?= Loc::getMessage($langPrefix . 'LOADING_OF') ?><span class="js-pdf-bundler__total">0</span></div>
	<div class="js-pdf-bundler__processing" style="display: none;"><?= Loc::getMessage($langPrefix . 'PROCESSING') ?></div>
	<div class="js-pdf-bundler__error" style="display: none;"><?= Loc::getMessage($langPrefix . 'ERROR') ?></div>
</div>
<script>
	(function() {
		const printer = new PdfPrinter('#pdf-bundler');
		const urls = <?= Json::encode($arResult['DOWNLOAD_LIST']) ?>;

		printer.load(urls);
	})();
</script>
