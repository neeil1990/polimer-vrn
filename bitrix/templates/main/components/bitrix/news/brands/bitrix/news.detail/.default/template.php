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

<div class="row cl">
    <h1 class="h1">Бренд <?=$arResult['NAME']?></h1>
    <img src="<?=$arResult['DETAIL_PICTURE']['SRC']?>" alt="<?=$arResult['NAME']?>">
    <?echo $arResult["DETAIL_TEXT"];?>
</div>

<h2 class="h2">Категории бренда <?=$arResult['NAME']?></h2>
<div class="catalog-sections-list cl">
    <?foreach ($arResult['SECTIONS'] as $arSection):?>
        <div class="catalog-sections-list__item">
            <a href="<?=$arSection['SECTION_PAGE_URL']?>filter/proizvoditel-is-<?=$arSection['VALUE_XML_ID']?>/apply/" class="link">
                <img alt="<?=$arSection['NAME']?>" src="<?=$arSection['PICTURE'];?>" height="120" class="img">
                <span class="name"><?=$arSection['NAME'];?></span>
            </a>
        </div>
    <?endforeach;?>
</div>

<!--end::catalog-sections-->
<h2 class="h2">Товары бренда <?=$arResult['NAME']?></h2>
<div class="row cl">
    <div class="products_roll">
        <div class="pr_box cl">
            <? foreach ($arResult['ITEMS'] as $key => $arItem): ?>
                <div class="item">
                    <div class="hover">
                        <div class="inner">
                            <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="pic">
								   <span>
									  <img src="<?=$arItem['PREVIEW_PICTURE']?>" alt="<?=$arItem['NAME']?>">
								   </span>
                            </a>
                            <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="title"><?=$arItem['NAME']?></a>
                            <div class="cost">
                                <span><?=price($arItem['ID']);?></span> &#8381;/<?=$arItem['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?>
                            </div>
                            <?if(!price($arItem['ID'])){
                                print '<span class="noprice">Цену уточняйте у менеджера</span>';
                            }
                            ?>
                            <div class="cost_total"><span></span></div>
                            <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="add2cartOrder" target="_blank">Подробнее</a>
                        </div>
                    </div>
                </div>
            <? endforeach; ?>
        </div>
    </div>
</div>
<!--end::catalog-element-->
