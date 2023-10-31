<?php

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return false;
}
?>

<style>
    .corsik_yadelivery__container {
        background: white;
        border-radius: 20px;
        padding: 32px;
    }

    .corsik_yadelivery__container img {
        width: 200px;
        height: 200px;
        border-radius: 30px;
    }

    .corsik_yadelivery__body {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .corsik_yadelivery__body input {
        margin-top: 40px !important;
        width: max-content;
    }

    .corsik_yadelivery__description {
        font-size: 16px;
    }
</style>

<div class="corsik_yadelivery__container">
    <div class="corsik_yadelivery__body">
        <?
        $image = '/bitrix/themes/.default/images/corsik.yadelivery/ya_delivery_logo.png';
        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $image)) {
            ?>
            <img alt="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LOGO_ALT') ?>"
                 src="<?= $image ?>"/>
            <?
        }
        ?>

        <h3><?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_HEADER') ?></h3>
        <input type="button" value="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_INSTALL_INPUT') ?>"
               title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_INSTALL_INPUT') ?>"
               class="adm-btn-save"
               onclick="document.location.href = '/bitrix/admin/corsik_yadelivery_delivery_setup.php?lang=ru'">
    </div>
</div>
