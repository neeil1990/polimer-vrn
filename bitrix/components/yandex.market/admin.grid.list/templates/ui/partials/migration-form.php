<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

?>
<form method="post" action="yamarket_migration.php?lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post(); ?>
	<button class="adm-btn" type="submit" name="run" value="Y" style="margin-bottom: 20px;"><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_GO_MIGRATION'); ?></button>
</form>
