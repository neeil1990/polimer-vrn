<?php
/**
 * Created: 11.03.2021, 18:25
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) return;
Loc::loadMessages(__FILE__);
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
?>
<div style="background-color: white; padding: 20px; border: 1px solid #c8d1d6;">
    <form action="<?= $APPLICATION->GetCurPage(); ?>">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
        <input type="hidden" name="id" value="<?= $moduleID ?>">
        <input type="hidden" name="uninstall" value="Y">
        <input type="hidden" name="step" value="2">
        <?php CAdminMessage::ShowMessage(Loc::getMessage('MOD_UNINST_WARN')) ?>
        <div style="margin: 20px 0;">
            <p><?= Loc::getMessage('MOD_UNINST_SAVE') ?></p>
            <p>
                <input type="checkbox" name="saveTables" id="saveTables" value="Y" checked
                       style="margin: 0 5px 0 0; padding: 0; vertical-align: middle;">
                <label for="saveTables" style="vertical-align: middle;"><?=
                    Loc::getMessage('MOD_UNINST_SAVE_TABLES') ?></label>
            </p>
            <p>
                <input type="checkbox" name="saveLogs" id="saveLogs" value="Y" checked
                       style="margin: 0 5px 0 0; padding: 0; vertical-align: middle;">
                <label for="saveLogs" style="vertical-align: middle;"><?=
                    Loc::getMessage('MOD_UNINST_SAVE_LOGS') ?></label>
            </p>
        </div>
        <div style="margin: 20px 0;">
            <input type="submit" name="inst" value="<?= Loc::getMessage('MOD_UNINST_DEL') ?>">
        </div>
    </form>
</div>