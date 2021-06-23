<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

?>
<form action="<?= POST_FORM_ACTION_URI ?>" method="post" target="_blank">
	<?php
	echo bitrix_sessid_post();
	?>
	<input type="hidden" name="action" value="print" />
	<input type="hidden" name="view" value="print" />
	<input type="hidden" name="entityType" value="<?= $arResult['ENTITY_TYPE']; ?>" />
