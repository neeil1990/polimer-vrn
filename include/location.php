<? if(!$arCityes = include $_SERVER['DOCUMENT_ROOT'].'/.cityes.php') return false; ?>

<a href="javascript:void(0)" class="header__adress" id="location_btn" title="Выберите город">
	<span style="border-bottom: 1px dashed #0358a6;">Ваш город: <?=($_COOKIE['city']) ?: 'Воронеж'?></span>
</a>

<div class="mfeedback-p" id="location" style="max-width: 500px;">
    <span class="button b-close"><span>&times;</span></span>
    <div class="mfeedback-p-head">Выбор города</div>

    <? if($arCityes['show'] || $arCityes['hide']): ?>
    <div class="city">
        <? if($arCityes['show']): ?>
        <div class="item-city">
            <? foreach ($arCityes['show'] as $s):?>
            <a href="javascript:void(0)" class="c"><?=$s?></a>
            <? endforeach; ?>
        </div>
        <? endif; ?>

        <div class="item-city">
            <a href="javascript:void(0)" class="city-show" onclick="$(this).closest('.city').find('.item-city:last-child').toggleClass('active'); return false;">Показать все города</a>
        </div>

        <? if($arCityes['hide']): ?>
        <div class="item-city">
            <? foreach ($arCityes['hide'] as $h):?>
            <a href="javascript:void(0)" class="c"><?=$h?></a>
            <? endforeach; ?>
        </div>
        <? endif; ?>
    </div>
    <? endif; ?>
</div>

<style>
    .mfeedback-p {
        max-width: 430px;
        text-align: center;
        display: none;
        background: #FFF;
    }
    .mfeedback-p .mfeedback-p-head {
        color: #545d6f;
        font-size: 24px;
        text-transform: uppercase;
        font-weight: 500;
        background: #f4f6f8;
        margin: 0;
        padding: 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid #E5E5E5;
    }
    .mfeedback-p .button.b-close {
        position: absolute;
        right: 10px;
        top: 10px;
        background: #FFF;
        border-radius: 50%;
        padding: 0px 3px 0px 3px;
        line-height: 18px;
        font-size: 21px;
        cursor: pointer;
        border: 1px solid #8080803b;
    }
    .city .item-city {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin: 10px auto;
        padding: 0 10px;
    }
    .city .item-city a {
        width: 140px;
        margin-bottom: 5px;
        color: #575b71;
        text-decoration: none;
    }
    .city .item-city a:hover {
        color: #c82c30;
    }
    .city .item-city a.city-show {
        flex-grow: 1;
        text-align: center;
    }
    .city .item-city:last-child{
        display: none;
    }
    .city .item-city:last-child.active{
        display: flex;
    }
	.custom-tooltip-styling-width{
		max-width: 130px;
	}
</style>

<script>
    $('#location_btn').click(function(){
        $('#location').bPopup({
            zIndex:1000,
            position: ['auto', 50]
        });
    });

    $('.city .item-city a.c').click(function(){
        var city = $(this).text();
        document.cookie = "city=" + city + "; path=/;";
        $('#location_btn').html('Ваш город: ' + city);

        var bPopup = $('#location').bPopup();
        bPopup.close();

        window.location.href = '/?city=' + city;
    });

	$( "#location_btn" ).tooltip({
      	track: true,
		tooltipClass: "custom-tooltip-styling-width",
    });
</script>
