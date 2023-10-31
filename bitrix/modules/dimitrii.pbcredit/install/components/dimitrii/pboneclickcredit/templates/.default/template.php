<?php
/**
 * Created by PhpStorm.
 * User: Dimitrii
 * Date: 27.06.16
 * Time: 11:36
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
CJSCore::Init(array('jquery'));
//Bitrix\Main\Page\Asset::getInstance()->addJs('https://my.pochtabank.ru/sdk/v1/pos-credit.js');
Bitrix\Main\Page\Asset::getInstance()->addString('<script type="text/javascript" src="https://my.pochtabank.ru/sdk/v1/pos-credit.js"></script>');
?>
<button id='pos-credit-one-click' class="<?=$arParams['CREDIT_BTN_CLASS']?>"><?=$arParams['CREDIT_BTN_NAME']?></button>
<div id="modal-post-credit" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div id="pos-credit-container" data-status="<?= $ORDER['PS_STATUS_CODE'] ?>"></div>
    </div>
</div>

<script type="text/javascript">
    window.pbcUrl = '/bitrix/admin/dimitrii.pbcredit_ajax.php?<?=bitrix_sessid_get()?>';
    window.pbcData = <?=CUtil::PhpToJSObject($arResult['DATA'], false, true)?>;
    window.pbcSettings = <?=CUtil::PhpToJSObject($arResult['PARAMS'], false, true)?>;
</script>