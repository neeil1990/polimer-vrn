<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');?>
<?
if (!class_exists('ps_uniteller') && file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/payment/step2use.uniteller/tools.php')) {
	include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/payment/step2use.uniteller/tools.php');
}
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:sale.personal.ordercheck.cancel',
	'',
	array(
		'PATH_TO_LIST' => '/personal/ordercheck/',
		'PATH_TO_DETAIL' => '/personal/ordercheck/detail/index.php?ID=' . $ID,
		'SET_TITLE' => 'Y',
		'ID' => $ID,
	),
	$component
);?>
<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>