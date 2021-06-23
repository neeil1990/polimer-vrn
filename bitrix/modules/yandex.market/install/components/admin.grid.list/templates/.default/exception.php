<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $this CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminFormEdit */

use Bitrix\Main\Localization\Loc;

$component = $this->__component;

$component->showErrors();

if ($arResult['EXCEPTION_MIGRATION'])
{
	?>
	<form method="post" action="yamarket_migration.php?lang=<?= LANGUAGE_ID ?>">
		<?= bitrix_sessid_post(); ?>
		<button class="adm-btn" type="submit" name="run" value="Y"><?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_GO_MIGRATION'); ?></button>
	</form>
	<?
}