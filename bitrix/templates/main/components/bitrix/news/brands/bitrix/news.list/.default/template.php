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
<div class="row">
    <div class="h1">По алфавиту</div>

    <div class="letters">
        <?if(count($arResult['FILTER_LITTER']['int'])):?>
            <ul class="letter-list">
                <? foreach ($arResult['FILTER_LITTER']['int'] as $key):?>
                    <li class="letter-item"><a href=#letter_<?=$key?>""><?=$key;?></a></li>
                <?endforeach;?>
            </ul>
        <?endif;?>

        <?if(count($arResult['FILTER_LITTER']['str_eng'])):?>
            <ul class="letter-list">
                <? foreach ($arResult['FILTER_LITTER']['str_eng'] as $key):?>
                    <li class="letter-item"><a href="#letter_<?=$key?>"><?=$key;?></a></li>
                <?endforeach;?>
            </ul>
        <?endif;?>

        <?if(count($arResult['FILTER_LITTER']['str_rus'])):?>
            <ul class="letter-list">
                <? foreach ($arResult['FILTER_LITTER']['str_rus'] as $key):?>
                    <li class="letter-item"><a href="#letter_<?=$key?>"><?=$key;?></a></li>
                <?endforeach;?>
            </ul>
        <?endif;?>
    </div>

</div>

<div class="row">

    <div class="h1">Все бренды</div>

    <? foreach($arResult['SECTION_ITEMS'] as $let => $arSection):?>
        <div class="letter" id="letter_<?=$let?>"><?=$let?></div>

        <div class="brand-list cl">
            <? foreach($arSection as $i => $arItems):?>
                <div class="brand-item <?if($i > 11):?>brand-hidden<?endif;?>">
                    <a href="<?=$arItems['DETAIL_PAGE_URL']?>">
                        <div class="img">
                            <img src="<?=($src = $arItems['PREVIEW_PICTURE']['SRC']) ? $src : SITE_TEMPLATE_PATH.'/img/no_photo.png'?>" height="60">
                        </div>
                        <div class="name"><?=$arItems['NAME']?></div>
                    </a>
                </div>
            <?endforeach;?>
            <?if($i > 11):?>
                <div class="more"><a href="#" class="show_brand">Показать ещё</a></div>
            <?endif;?>
        </div>
    <?endforeach;?>

</div>

