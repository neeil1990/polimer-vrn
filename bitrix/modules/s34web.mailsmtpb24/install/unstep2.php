<?php
/**
 * Created: 23.03.2021, 23:06
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
if (!check_bitrix_sessid()) return;
?>
<div style="background-color: white; padding: 20px; border: 1px solid #c8d1d6;">
    <?php
    if ($ex = $APPLICATION->GetException()) {
        CAdminMessage::ShowMessage(array(
            'TYPE' => 'ERROR',
            'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
            'DETAILS' => $ex->GetString(),
            'HTML' => true,
        ));
    } else {
        CAdminMessage::ShowNote(Loc::getMessage('MOD_UNINST_OK'));
    }
    ?>
    <form action="<?= $APPLICATION->GetCurPage(); ?>">
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
        <div style="margin: 20px 0;">
            <input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
        </div>
    </form>
</div>