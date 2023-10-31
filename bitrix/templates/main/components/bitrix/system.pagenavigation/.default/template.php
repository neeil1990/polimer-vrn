<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if ($arResult["NavShowAlways"] || $arResult["NavPageCount"] > 1):?>

    <div class="ns__paginator cl">
        <div class="name">Страницы:</div>
        <?if ($arResult["NAV"]["PAGE_NUMBER"] == 1):?>
            <a href="#" class="arrow left"><span></span><span></span></a>
        <?else:?>
            <a href="<?=$arResult["NAV"]["URL"]["FIRST_PAGE"]?>" class="arrow left aractive"><span></span><span></span></a>
        <?endif;?>

        <div class="pages cl">

        <?for ($PAGE_NUMBER=$arResult["NAV"]["START_PAGE"]; $PAGE_NUMBER<=$arResult["NAV"]["END_PAGE"]; $PAGE_NUMBER++):?>
            <?if ($PAGE_NUMBER == $arResult["NAV"]["PAGE_NUMBER"]):?>
                <a href="" class="page active"><?=$PAGE_NUMBER?></a>
            <?else:?>
                <a href="<?=$arResult["NAV"]["URL"]["SOME_PAGE"][$PAGE_NUMBER]?>" class="page"><?=$PAGE_NUMBER?></a>
            <?endif;?>
        <?endfor;?>

        </div>

        <?if ($arResult["NAV"]["PAGE_NUMBER"] == $arResult["NAV"]["PAGE_COUNT"]):?>
            <a href="#" class="arrow right"><span></span><span></span></a>
        <?else:?>
            <a href="<?=$arResult["NAV"]["URL"]["NEXT_PAGE"]?>" class="arrow right aractive"><span></span><span></span></a>
        <?endif;?>

    </div>
<?endif;?>