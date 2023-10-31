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

CJSCore::Init(['ui.vue3', 'ui.vue.vuex', 'ui.notification']);
$context = Main\Application::getInstance()->getContext();
$request = $context->getRequest();
?>
<?php
$jsResult = [
    'DATA_VUE' => $arResult['DATA_VUE']
];
$this->addExternalJs($templateFolder . '/js/component.js');
$documentRoot = Main\Application::getDocumentRoot();
$jsTemplates = new Main\IO\Directory($documentRoot . $templateFolder . '/js-templates');
foreach ($jsTemplates->getChildren() as $jsTemplate) {
    include $jsTemplate->getPath();
}
?>
<div id='vue-settings-category'></div>
<script>
    $(function () {
        <?='BX.message(' . CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>
        BX.Ozon.SettingsCategoryList.Vue.init({
            ajaxUrl: '<?=CUtil::JSEscape($arResult['PATH_TO_AJAX'])?>',
            ajaxImportUrl: '<?=CUtil::JSEscape($arResult['PATH_TO_AJAX_IMPORT'])?>',
            signedParams: '<?=CUtil::JSEscape($arResult['SIGNED_PARAMS'])?>',
            tree: <?=CUtil::PhpToJSObject($jsResult['DATA_VUE']['TREE'])?>,
            selected: <?=CUtil::PhpToJSObject($jsResult['DATA_VUE']['SELECTED'])?>,
        })
    })
</script>