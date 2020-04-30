<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var $this CBitrixComponentTemplate */
?>

<div class="prime-banner-bottom" id="show-<?=$arResult['ID']?>">
    <span class="close-p-bottom" onclick="closeBannerBottom(this)">&times;</span>
    <a href="<?=$arResult['URL']?>"
       <?if($arResult['OPEN_NEW_WINDOW'] == "Y"):?>target="_blank"<?endif;?>
       <?if($arResult['URL']):?>onclick="statBannerBottom('CLICKS');closeBannerBottom(this);"<?endif;?>>
        <img src="<?=$arResult['IMAGE']?>" alt="<?=$arResult['NAME']?>" title="<?=$arResult['NAME']?>">
    </a>
</div>

<script>

    <?if($arResult['SHOW_OUT_SITE'] == "Y"):?>
    var alert_flag = true;
    document.onmouseout = handler;
    function handler(event){
        if (event.clientY < 0 && alert_flag) {
                alert_flag = false;
                showBannerBottom();
        }
    }
    <?endif;?>

    function statBannerBottom(FIELD){
        BX.ajax({
            url: '<?=$templateFolder.'/ajax.php';?>',
            data: {
                ID : <?=$arResult['ID']?>,
                FIELD_BANNER : FIELD
            },
            method: 'POST',
            dataType: 'json',
            timeout: 30,
            async: true,
            processData: true,
            scriptsRunFirst: true,
            emulateOnload: true,
            start: true,
            cache: false,
        });
    }

    function closeBannerBottom(El){
        document.cookie = 'primeBannerClose-<?=$arResult['ID']?>=true';
        document.getElementById('show-<?=$arResult['ID']?>').style.bottom='-90px';
    }


    function showBannerBottom() {
        statBannerBottom("SHOWS");
        document.getElementById('show-<?=$arResult['ID']?>').style.bottom='0px';
    }

    <?if($arResult['SHOW_TIME'] == "Y"):?>
    setTimeout(showBannerBottom, <?=$arResult['PAUSE'];?>);
    <?endif;?>

</script>


