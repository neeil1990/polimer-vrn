<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var $this CBitrixComponentTemplate */
?>

<div class="prime-banner-top" id="show-<?=$arResult['ID']?>">
    <span class="close-p-top" onclick="closeBannerTop(this)">&times;</span>
    <a href="<?=$arResult['URL']?>"
       <?if($arResult['OPEN_NEW_WINDOW'] == "Y"):?>target="_blank"<?endif;?>
       <?if($arResult['URL']):?>onclick="statBannerTop('CLICKS');closeBannerTop(this);"<?endif;?>>
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
                showBannerTop();
        }
    }
    <?endif;?>

    function statBannerTop(FIELD){
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

    function closeBannerTop(El){
        document.cookie = 'primeBannerClose-<?=$arResult['ID']?>=true';
        document.getElementById('show-<?=$arResult['ID']?>').style.top='-90px';
    }


    function showBannerTop() {
        statBannerTop("SHOWS");
        document.getElementById('show-<?=$arResult['ID']?>').style.top='0px';
    }

    <?if($arResult['SHOW_TIME'] == "Y"):?>
    setTimeout(showBannerTop, <?=$arResult['PAUSE'];?>);
    <?endif;?>

</script>


