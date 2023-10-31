<?php
if (!check_bitrix_sessid()) {
    return;
}

global $APPLICATION;

if ($ex = $APPLICATION->GetException()) {
    echo CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => GetMessage('MOD_UNINST_ERR'),
        'DETAILS' => $ex->GetString(),
        'HTML' => true,
    ]);
} else {
    echo CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK"));
}

?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<? echo LANG ?>">
    <input type="submit" name="" value="<? echo GetMessage('MOD_BACK') ?>">
</form>
