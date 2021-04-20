<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("1");

if(empty($_REQUEST['ORDER_ID'])){

	$basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
	$context = new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId());
	$discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, $context);
	$r = $discounts->calculate();
	if ($r->isSuccess())
	{
		$result = $r->getData();
	}
}
?>

<? $APPLICATION->IncludeComponent(
	"nbrains:sale.order.full",
	"make-order",
	array(
		"DISCOUNT_PRICE_D7" => $result['BASKET_ITEMS'],
		"ALLOW_PAY_FROM_ACCOUNT" => "Y",
		"CITY_OUT_LOCATION" => "Y",
		"COUNT_DELIVERY_TAX" => "N",
		"COUNT_DISCOUNT_4_ALL_QUANTITY" => "Y",
		"DELIVERY_NO_SESSION" => "N",
		"ONLY_FULL_PAY_FROM_ACCOUNT" => "N",
		"PATH_TO_AUTH" => "/auth.php",
		"PATH_TO_BASKET" => "/personal/orders-list.php",
		"PATH_TO_PAYMENT" => "payment.php",
		"PATH_TO_PERSONAL" => "/personal/orders-list.php",
		"PRICE_VAT_INCLUDE" => "N",
		"PRICE_VAT_SHOW_VALUE" => "N",
		"PROP_1" => array(
			0 => "30",
			1 => "31",
			2 => "34",
		),
		"PROP_2" => array(
			0 => "32",
			1 => "33",
			2 => "35",
		),
		"SEND_NEW_USER_NOTIFY" => "Y",
		"SET_TITLE" => "Y",
		"SHOW_AJAX_DELIVERY_LINK" => "Y",
		"SHOW_MENU" => "N",
		"USE_AJAX_LOCATIONS" => "Y",
		"COMPONENT_TEMPLATE" => "make-order"
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
