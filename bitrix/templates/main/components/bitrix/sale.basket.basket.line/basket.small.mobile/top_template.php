<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$compositeStub = (isset($arResult['COMPOSITE_STUB']) && $arResult['COMPOSITE_STUB'] == 'Y');

?>
<a href="<?= $arParams['PATH_TO_BASKET'] ?>" class="hmobile__cart cart">
	<span class="cart__number"><? echo $arResult['NUM_PRODUCTS']; ?></span>
</a>


