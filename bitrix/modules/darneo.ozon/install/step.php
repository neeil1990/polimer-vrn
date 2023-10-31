<?php

if (!check_bitrix_sessid()) {
    return;
} ?>
<div class='adm-info-message-wrap adm-info-message-green'>
    <div class='adm-info-message'>
        <div class='adm-info-message-title'><?= GetMessage('DARNEO_OZON_MODULE_INSTALL_OK') ?></div>
        <div class='adm-info-message-icon'></div>
    </div>
</div>
<form action='/bitrix/admin/wizard_list.php'>
    <input type='hidden' name='lang' value='ru'>
    <input type='submit' name='' value='<?= GetMessage('DARNEO_OZON_OPEN_WIZARDS_LIST') ?>'
           style='margin-right: 10px;'>
    <input type='button' value='<?= GetMessage('DARNEO_OZON_INSTALL_SERVICE') ?>' style='margin-right: 30px;'
           onclick='document.location.href="/bitrix/admin/wizard_install.php?lang=ru&wizardName=darneo.ozon:darneo:darneo_ozon&<?= bitrix_sessid_get(
           ) ?>";'>
    <form>
