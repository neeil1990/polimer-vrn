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


<a href="/catalog/" class="catalog__trigger">
	<span class="i1"></span>
	<span class="i2"></span>
	<span class="i3"></span>
</a>

<a href="/catalog/"  class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu">
	<div class="wr">
		<ul class="first-sections">

			<?
			$inc = 1;
			foreach($arResult['SECTIONS'] as $key => $section): ?>
			<li>
				<a href="<?=$section['SECTION_PAGE_URL']?>">
					<img src="<?=SITE_TEMPLATE_PATH?>/img/category_<?=$inc;?>.png" width="90" height="65" alt="<?=$section['NAME']?>" />
					<?=str_replace(' ','<br>',$section['NAME'])?>
				</a>
				<div class="subsections">
					<?
					$col_arr = ceil(count($section['SECTION_1'])/3);
					$three_sect = array_chunk($section['SECTION_1'], $col_arr);
					for($i = 0; $i < count($three_sect);$i++):
					?>
					<ul>
					<? foreach($three_sect[$i] as $sec_2): ?>

						<li >
							<? if(count($sec_2['SECTION_2']) != 0): ?><a class="dd" href="#">+</a><? endif; ?>
							<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="title"><?=$sec_2['NAME']?></a>
							<ul class="inner">
							<? foreach($sec_2['SECTION_2'] as $sec_3): ?>
								<li><a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=$sec_3['NAME']?></a></li>
							<? endforeach; ?>
							</ul>
						</li>

					<? endforeach; ?>
					</ul>
					<? endfor; ?>
				</div>
			</li>
			<?
			$inc++;
			endforeach; ?>
		</ul>
	</div>
</div>