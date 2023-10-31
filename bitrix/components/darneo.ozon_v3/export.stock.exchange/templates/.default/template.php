<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

CJSCore::Init(['ui.vue3', 'ui.vue.vuex', 'ui.notification', 'darneo_ozon.select']);
$context = Main\Application::getInstance()->getContext();
$request = $context->getRequest();
?>
<?php
$jsResult = [
    'DATA_VUE' => $arResult['DATA_VUE']
];
$this->addExternalJs($templateFolder . '/js/component.js');
$this->addExternalJs(SITE_TEMPLATE_PATH . '/js/json/jquery.json-editor.min.js');
$this->addExternalCss(SITE_TEMPLATE_PATH . '/js/json/jquery.json-viewer.css');
$documentRoot = Main\Application::getDocumentRoot();
$jsTemplates = new Main\IO\Directory($documentRoot . $templateFolder . '/js-templates');
foreach ($jsTemplates->getChildren() as $jsTemplate) {
    include $jsTemplate->getPath();
}
?>
<div id='vue-export-exchange'></div>
<?php if (!$arResult['DATA_VUE']['DISABLE_OPTIMISATION']): ?>
    <div class='alert alert-primary d-flex align-items-center p-5 mb-5 mt-5'>
        <i class='ki-duotone ki-shield-tick fs-2hx text-primary me-4'><span class='path1'></span><span class='path2'></span></i>
        <div class='d-flex flex-column'>
            <h4 class='mb-1 text-primary'><?= Loc::getMessage('DARNEO_OZON_VUE_STOCK_WARNING_H1') ?></h4>
            <span><?= Loc::getMessage('DARNEO_OZON_VUE_STOCK_WARNING') ?></span>
        </div>
    </div>
<?php endif; ?>
<script>
    $(function () {
        <?='BX.message(' . CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>
        BX.Ozon.ExportExchange.Vue.init({
            ajaxUrl: '<?=CUtil::JSEscape($arResult['PATH_TO_AJAX'])?>',
            signedParams: '<?=CUtil::JSEscape($arResult['SIGNED_PARAMS'])?>',
            data: <?=CUtil::PhpToJSObject($jsResult['DATA_VUE'])?>
        })
    })
</script>