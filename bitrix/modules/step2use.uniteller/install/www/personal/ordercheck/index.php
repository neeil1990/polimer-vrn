<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');?>
<?
if (!class_exists('ps_uniteller') && file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/payment/step2use.uniteller/tools.php')) {
	include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/step2use.uniteller/payment/step2use.uniteller/tools.php');
}
?>
<?
$APPLICATION->IncludeComponent('bitrix:sale.personal.ordercheck', 'list', array(
	'PROP_1' => Array(0 => '5'), 'PROP_2' => Array(0 => '18'),
	'SEF_MODE' => 'Y',
	'SEF_FOLDER' => '/personal/ordercheck/',
	'ORDERS_PER_PAGE' => '10',
	'PATH_TO_PAYMENT' => '/personal/ordercheck/payment/',
	'PATH_TO_BASKET' => '/personal/cart/',
	'SET_TITLE' => 'Y',
	'SAVE_IN_SESSION' => 'N',
	'NAV_TEMPLATE' => 'arrows',
	'SEF_URL_TEMPLATES' => array(
		'list' => 'index.php',
		'detail' => 'detail/index.php?ID=#ID#',
		'cancel' => 'cancel/index.php?ID=#ID#',
		'check' => 'check/index.php',
	)
	),
	false
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>