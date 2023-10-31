<?php
global $APPLICATION;
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="corsik.yadelivery">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <?= CAdminMessage::ShowMessage(GetMessage('MOD_UNINST_WARN')) ?>
    <h3><?= GetMessage('CORSIK_DELIVERY_SERVICE_UNINSTALL_STEP1_TITLE') ?></h3>
    <p>
        <input type="checkbox" name="save_table" id="save_table" value="Y" checked>
        <label for="save_table"><?= GetMessage('CORSIK_DELIVERY_SERVICE_SAVE_TABLES') ?></label>
    </p>
    <p>
        <input type="checkbox" name="save_data" id="save_data" value="Y" checked>
        <label for="save_data"><?= GetMessage('CORSIK_DELIVERY_SERVICE_SAVE_DATA') ?></label>
    </p>
    <input type="submit" name="inst" value="<?= GetMessage('MOD_UNINST_DEL') ?>">
</form>
