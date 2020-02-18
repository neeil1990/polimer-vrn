<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<?if($arResult['SECTIONS']):?>
<div class="product_top cl">

    <div class="catalog_top cl">
        <? foreach($arResult['SECTIONS'] as &$arSection):?>
            <div class="item_c">
                <a href="<?=$arSection['SECTION_PAGE_URL']?>">
                    <div class="img_c">
                        <img src="<?=resizeImage($arSection['PICTURE']['ID'], 140, 120);?>" alt="<?=$arSection['NAME']?>">
                    </div>
                    <div class="name_c"><?=$arSection['NAME']?></div>
                </a>
            </div>
        <? endforeach; ?>
    </div>

</div>
<!--end::catalog-sections-->

<? endif; ?>
