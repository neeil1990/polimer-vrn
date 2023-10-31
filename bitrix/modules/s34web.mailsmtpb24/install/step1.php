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
$moduleCode = strtoupper(str_replace(".", "_", $moduleID));
$sites = [];
$resSites = Bitrix\Main\SiteTable::getList([
    'order' => ['SORT' => 'asc'],
    'filter' => ['ACTIVE' => 'Y'],
    'select' => ['LID', 'NAME', 'DIR']
]);
while ($arSite = $resSites->fetch()) {
    $sites[$arSite['NAME'] . ' [ ' . $arSite['LID'] . ' - "' . $arSite['DIR'] . '" ]'] = $arSite['LID'];
}
if (empty($sites)) {
    CAdminMessage::ShowMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
            'DETAILS' => Loc::getMessage($moduleCode . '_EMPTY_SITES_ERROR_TEXT') .
                '<a href="/bitrix/admin/site_admin.php?lang=' . LANGUAGE_ID . '" target="_blank">' .
                Loc::getMessage($moduleCode . '_EMPTY_SITES_ERROR_LINK_TEXT') . '</a>!',
            'HTML' => true
        ]
    );
} else {
    ?>
    <div style="background-color: #fff; padding: 20px; border: 1px solid #c8d1d6;">
        <form action="<?= $APPLICATION->GetCurPage(); ?>">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
            <input type="hidden" name="id" value="<?= $moduleID ?>">
            <input type="hidden" name="install" value="Y">
            <input type="hidden" name="step" value="2">
            <p><?= Loc::getMessage($moduleCode . '_SELECT_SITE_TEXT') ?>:</p>
            <div style="margin: 20px 0;">
                <?= SelectBoxFromArray(
                    'siteID',
                    [
                        'REFERENCE' => array_keys($sites),
                        'REFERENCE_ID' => array_values($sites)
                    ]
                ); ?>
            </div>
            <div style="margin: 20px 0;">
                <input type="submit" name="inst" value="<?= Loc::getMessage('MOD_INSTALL') ?>">
            </div>
        </form>
    </div>
    <?php
}
