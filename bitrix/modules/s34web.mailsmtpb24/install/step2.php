<?php
/**
 * Created: 12.07.2021, 15:06
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) return;
Loc::loadMessages(__FILE__);
?>
<div style="background-color: #fff; padding: 20px; border: 1px solid #c8d1d6;">
    <?php
    if ($ex = $APPLICATION->GetException()) {
        CAdminMessage::ShowMessage(array(
            'TYPE' => 'ERROR',
            'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
            'DETAILS' => $ex->GetString(),
            'HTML' => true,
        ));
    } else {
        CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
    }
    ?>
<form action="<?= $APPLICATION->GetCurPage(); ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <div style="margin: 20px 0;">
        <input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
    </div>
</form>
</div>