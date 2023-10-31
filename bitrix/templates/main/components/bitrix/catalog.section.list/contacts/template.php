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
?><script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
<!--<div class="co__sandwich">
	<a href="#" class="sandwich">
		<span></span>
		<span></span>
		<span></span>
	</a>
</div>-->

<div class="co__cities">
	<? foreach ($arResult['SECTIONS'] as $key => &$arSection): ?>
	<a href="#" class="city <?if($key == 0){?>active<?}?>"><?=$arSection['NAME']?></a>
	<? endforeach; ?>
</div><!--end::co__cities-->

<div class="co__cities-list">

	<? foreach ($arResult['SECTIONS'] as $key => &$arSection): ?>
	<div class="city <?if($key == 0){?>active<?}?>">
		<div class="co__tabs">
			<div class="t-list cl">
		<? foreach ($arSection['ITEM'] as $key => &$arItem): ?>
				<a href="#" class="<?if($key == 0){?>active<?}?>"><span><?=$arItem['NAME']?></span></a>
		<?endforeach?>
			</div>
			<div class="t-content">
				<? foreach ($arSection['ITEM'] as $key => &$arItem): ?>
				<div class="tab cl <?if($key == 0){?>active<?}?>">
					<div class="col-title"><span><?=$arItem['NAME']?></span></div>

					<div class="col-text">
						<? if($arItem['STRET']['VALUE']): ?>
						<div class="txt locate cl"><?=$arItem['STRET']['VALUE']?></div>
						<? endif;?>
						<div class="block-cont left">
							<? if(empty($arItem['DEL_CATEGORY']['VALUE'])): ?>
							<div class="tit insant">Инженерная<br>сантехника</div>
							<? endif;?>
							<? if($arItem['TITLE']['VALUE']): ?>
								<div class="tit"><?=$arItem['TITLE']['VALUE']?></div>
							<? endif;?>
							<? if($arItem['ING_PHONE']['VALUE']): ?>
							<div class="txt phone">
                                <a href="tel:<?=tel($arItem['ING_PHONE']['VALUE'])?>" class="phone_engineer"><?=$arItem['ING_PHONE']['VALUE']?></a>
                            </div>
							<? endif;?>
							<? if($arItem['ING_MAIL']['VALUE']): ?>
							<a href="mailto:<?=$arItem['ING_MAIL']['VALUE']?>" class="mail"><?=$arItem['ING_MAIL']['VALUE']?></a>
							<? endif;?>
							<? if($arItem['ING_TIME_W']['VALUE']): ?>
							<div class="time">
								<span>часы работы:</span>
								<? foreach($arItem['ING_TIME_W']['VALUE'] as $v):?>
								<span><?=$v?></span>
								<? endforeach; ?>
							</div>
							<? endif;?>
						</div>
						<div class="block-cont right">
							<? if(empty($arItem['DEL_CATEGORY']['VALUE'])): ?>
							<div class="tit stroma">Строительные<br>материалы, ворота<br>автоматика, рольставни</div>
							<? endif;?>
							<? if($arItem['STR_PHONE']['VALUE']): ?>
							<div class="txt phone">
                                <a href="tel:<?=tel($arItem['STR_PHONE']['VALUE'])?>" class="phone_building"><?=$arItem['STR_PHONE']['VALUE']?></a>
                            </div>
							<? endif;?>
							<? if($arItem['STR_MAIL']['VALUE']): ?>
							<a href="mailto:<?=$arItem['STR_MAIL']['VALUE']?>" class="mail"><?=$arItem['STR_MAIL']['VALUE']?></a>
							<? endif;?>
							<? if($arItem['STR_TIME_W']['VALUE']): ?>
							<div class="time">
								<span>часы работы:</span>
								<? foreach($arItem['STR_TIME_W']['VALUE'] as $v):?>
									<span><?=$v?></span>
								<? endforeach; ?>
							</div>
							<? endif;?>
						</div>
						<? if($arItem['PLAN_STRET']['VALUE']): ?>
						<a target="_blank" href="<?=CFile::GetPath($arItem['PLAN_STRET']['VALUE']);?>" class="dlmap">Скачать схему проезда</a>
						<? endif;?>
					</div>
					<?
					$map = explode(',',$arItem['MAP_API']['VALUE']);
					?>
					<div class="col-visual">
						<? if($arItem['PREVIEW_PICTURE']['VALUE']): ?>
						<div class="cw-row image"><img src="<?=CFile::GetPath($arItem['PREVIEW_PICTURE'])?>" alt="<?=$arItem['NAME']?>"></div>
						<? endif;?>
						<? if($arItem['MAP_API']['VALUE']): ?>
						<div class="cw-row map" id="map_<?=$arSection['ID'].$arItem['ID']?>"></div>
						<? endif;?>
					</div>
					<script type="text/javascript">
						ymaps.ready(init);
						var myMap;
						function init(){
							myMap = new ymaps.Map("map_<?=$arSection['ID'].$arItem['ID']?>", {
								center: [<?=$map[0]?>, <?=$map[1]?>],
								zoom: 16
							});
							myPlacemark = new ymaps.Placemark([<?=$map[0]?>, <?=$map[1]?>],   {
								iconCaption: '<?=$arItem['STRET']['VALUE']?>'
							}, {
								preset: 'islands#redDotIconWithCaption'
							});

							myMap.geoObjects.add(myPlacemark);
						}
					</script>

					<? if($arItem['ID'] == 24): ?>
					<div class="co__heads cl">
						<div class="rh-col">
							<div class="lvl">Директор</div>
							<div class="name">Рябцев Сергей Геннадьевич</div>
							<div class="phone">тел: <a href="tel:+74732075505">(473) 207-55-05</a> <span>добавочный 201</span></div>
							<div class="mail">e-mail: <a href="mailto: rsg@polimer-vrn.ru">rsg@polimer-vrn.ru</a></div>
						</div>
						<div class="rh-col">
							<div class="lvl">Начальник отдела снабжения</div>
							<div class="name">Старцев Дмитрий Олегович</div>
							<div class="phone">тел: <a href="tel:+74732075505">(473) 207-55-05</a> <span>добавочный 231</span></div>
							<div class="mail">e-mail: <a href="mailto: dmitry@polimer-vrn.ru">dmitry@polimer-vrn.ru</a></div>
						</div>
						<div class="rh-col">
							<div class="lvl">Начальник отдела продаж</div>
							<div class="name">Попова Оксана Сергеевна</div>
							<div class="phone">тел: <a href="tel:+74732075505">(473) 207-55-05</a> <span>добавочный 155</span></div>
							<div class="mail">e-mail: <a href="mailto: popova@polimer-vrn.ru">popova@polimer-vrn.ru</a></div>
						</div>
<!--
						<div class="rh-col">
							<div class="lvl">Начальник отдела оптовых продаж</div>
							<div class="name">Пелишенко Олег Валерьевич</div>
							<div class="phone">тел: (473) 207-55-06 <span>добавочный 323</span></div>
<div class="mail">e-mail: <a href="mailto:oleg@polimer-vrn.ru">oleg@polimer-vrn.ru</a></div>
						</div>
-->
					</div><!--end::co__heads-->
					<? endif; ?>

				</div>
				<?endforeach?>
			</div>
		</div><!--end::co__tabs-->
	</div><!--end::city-->
	<? endforeach; ?>

</div><!--end::co__cities-list-->
