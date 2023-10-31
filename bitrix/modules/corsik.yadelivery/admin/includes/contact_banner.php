<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
$module_id = Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID");
?>

<link href="/bitrix/css/<?= $module_id ?>/admin/admin.setup.css" type="text/css" rel="stylesheet">
<div class="corsik_setup__nav">
    <div class="corsik_setup__navItem corsik_setup__navDoc">
        <a target="_blank"
           href="https://gitlab.com/bx_modules/corsik.yadelivery/-/wikis"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_DOCUMENTATION"); ?></a>
    </div>
    <div class="corsik_setup__navItem corsik_setup__navReview">
        <a target="_blank"
           href="https://marketplace.1c-bitrix.ru/solutions/corsik.yadelivery/#tab-rating-link"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_COMMENT"); ?></a>
    </div>
    <div class="corsik_setup__navItem corsik_setup__navEmail">
        <a target="_blank"
           href="mailto:marketplace@corsik.ru"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_FEEDBACK"); ?></a>
    </div>
</div>
