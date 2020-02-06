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




<div class="cl">

    <div class="h1" style="margin-top: 30px"><?=$arResult['PROPERTIES']['TOP_TITLE']['VALUE']?></div>

	<div class="nd__content">

		<?
		if($arResult['PROPERTIES']['ADD_PROD']['VALUE']):
			?>
			<div class="slider_product articles" id="mp__product__action" style="max-width: 840px;margin-bottom: 10px;border: 0px">
				<?
				   $items = GetIBlockElementList(21, $arResult['PROPERTIES']['ADD_PROD']['VALUE'], Array("SORT"=>"ASC"), 50);
				   while($arFields = $items->GetNext())
				   {
						?>
						<div>
							<div class="product" style="border-left: 0px;">
								<a href="<?=$arFields["DETAIL_PAGE_URL"]?>" style="display: block;height: 120px;border-bottom: 0px;">
									<img src="<?=CFile::GetPath($arFields["PREVIEW_PICTURE"]);?>" alt="<?=$arFields["NAME"]?>" style="max-height: 110px;margin: 0 auto;" class="img">
								</a>
								<a href="<?=$arFields["DETAIL_PAGE_URL"]?>" class="name" style="border-bottom: 0px;"><?=$arFields["NAME"]?></a>
							</div>
						</div>
						<?
					}
				?>
			</div><!-- end::slider_product -->

		<? endif; ?>

		<div class="date">
			<?if($arParams["DISPLAY_DATE"]!="N" && $arResult["DISPLAY_ACTIVE_FROM"]):?>
				<?=$arResult["DISPLAY_ACTIVE_FROM"]?>
			<?endif;?>
		</div>
		<h1 class="title"><?=$arResult['PROPERTIES']['DETALI_TITLE']['VALUE']?></h1>
		<div class="txt">
			<a href="<?=$arParams['SECTION_URL']?>" class="back2allnews"><span></span><span></span>Назад к списку статей</a>

			<?if(strlen($arResult["DETAIL_TEXT"])>0):?>
				<?echo $arResult["DETAIL_TEXT"];?>
			<?endif?>

		</div>
		<div class="bottombar cl">
			<a href="<?=$arParams['SECTION_URL']?>" class="back2allnews"><span></span><span></span>Назад к списку статей</a>
			<div class="soc_share">
				<div class="ss_social">
					<div class="social-likes">
						<div class="facebook" title="Поделиться ссылкой на Фейсбуке">Facebook</div>
						<div class="twitter" title="Поделиться ссылкой в Твиттере">Twitter</div>
						<div class="vkontakte" title="Поделиться ссылкой во Вконтакте">Вконтакте</div>
						<div class="plusone" title="Поделиться ссылкой в Гугл-плюсе">Google+</div>
					</div>
				</div>

				<div class="ss_title">Поделиться ссылкой:</div>
			</div>
		</div>
	</div>

	<?$APPLICATION->IncludeComponent("bitrix:news.list", "sale-list", Array(
		"ACTIVE_DATE_FORMAT" => "j F Y",
		"ADD_SECTIONS_CHAIN" => "N",	// Включать раздел в цепочку навигации
		"AJAX_MODE" => "N",	// Включить режим AJAX
		"AJAX_OPTION_ADDITIONAL" => "",	// Дополнительный идентификатор
		"AJAX_OPTION_HISTORY" => "N",	// Включить эмуляцию навигации браузера
		"AJAX_OPTION_JUMP" => "N",	// Включить прокрутку к началу компонента
		"AJAX_OPTION_STYLE" => "Y",	// Включить подгрузку стилей
		"CACHE_FILTER" => "N",	// Кешировать при установленном фильтре
		"CACHE_GROUPS" => "Y",	// Учитывать права доступа
		"CACHE_TIME" => "36000000",	// Время кеширования (сек.)
		"CACHE_TYPE" => "A",	// Тип кеширования
		"CHECK_DATES" => "Y",	// Показывать только активные на данный момент элементы
		"DETAIL_URL" => "",	// URL страницы детального просмотра (по умолчанию - из настроек инфоблока)
		"DISPLAY_BOTTOM_PAGER" => "Y",	// Выводить под списком
		"DISPLAY_DATE" => "N",	// Выводить дату элемента
		"DISPLAY_NAME" => "Y",	// Выводить название элемента
		"DISPLAY_PICTURE" => "Y",	// Выводить изображение для анонса
		"DISPLAY_PREVIEW_TEXT" => "Y",	// Выводить текст анонса
		"DISPLAY_TOP_PAGER" => "N",	// Выводить над списком
		"FIELD_CODE" => array(	// Поля
			0 => "",
			1 => "",
		),
		"FILTER_NAME" => "",	// Фильтр
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",	// Скрывать ссылку, если нет детального описания
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],	// Код информационного блока
		"IBLOCK_TYPE" => "news",	// Тип информационного блока (используется только для проверки)
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Включать инфоблок в цепочку навигации
		"INCLUDE_SUBSECTIONS" => "Y",	// Показывать элементы подразделов раздела
		"MESSAGE_404" => "",	// Сообщение для показа (по умолчанию из компонента)
		"NEWS_COUNT" => "5",	// Количество новостей на странице
		"PAGER_BASE_LINK_ENABLE" => "N",	// Включить обработку ссылок
		"PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
		"PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
		"PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
		"PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
		"PAGER_TITLE" => "статьи",	// Название категорий
		"LINK_TITLE" => "articles",	// Название категорий
		"PARENT_SECTION" => "",	// ID раздела
		"PARENT_SECTION_CODE" => "",	// Код раздела
		"PREVIEW_TRUNCATE_LEN" => "50",	// Максимальная длина анонса для вывода (только для типа текст)
		"PROPERTY_CODE" => array(	// Свойства
			0 => "",
			1 => "",
		),
		"SET_BROWSER_TITLE" => "N",	// Устанавливать заголовок окна браузера
		"SET_LAST_MODIFIED" => "N",	// Устанавливать в заголовках ответа время модификации страницы
		"SET_META_DESCRIPTION" => "N",	// Устанавливать описание страницы
		"SET_META_KEYWORDS" => "N",	// Устанавливать ключевые слова страницы
		"SET_STATUS_404" => "N",	// Устанавливать статус 404
		"SET_TITLE" => "N",	// Устанавливать заголовок страницы
		"SHOW_404" => "N",	// Показ специальной страницы
		"SORT_BY1" => "ACTIVE_FROM",	// Поле для первой сортировки новостей
		"SORT_BY2" => "SORT",	// Поле для второй сортировки новостей
		"SORT_ORDER1" => "DESC",	// Направление для первой сортировки новостей
		"SORT_ORDER2" => "ASC",	// Направление для второй сортировки новостей
	),
		false
	);?>

</div>
