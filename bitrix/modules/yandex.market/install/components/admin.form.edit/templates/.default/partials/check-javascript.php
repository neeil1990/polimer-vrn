<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

?>
<div id="ym-check-jquery" style="display: none;">
	<?
	$jqueryPath = '/bitrix/js/main/jquery/jquery-1.8.3.min.js';
	$jqueryExtension = CJSCore::getExtInfo('jquery');

	if (isset($jqueryExtension['js']))
	{
		$jqueryPath = is_array($jqueryExtension['js']) ? reset($jqueryExtension['js']) : $jqueryExtension['js'];
	}

	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_JQUERY_NOT_FOUND'),
		'DETAILS' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_JQUERY_NOT_FOUND_DETAILS', [
			'#JQUERY_PATH#' => $jqueryPath
		]),
		'HTML' => true
	]);
	?>
</div>
<script>
	if (typeof jQuery === 'undefined')
	{
		document.getElementById('ym-check-jquery').style.display = 'block';
	}
</script>