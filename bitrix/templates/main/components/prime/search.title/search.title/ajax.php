<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(!empty($arResult["CATEGORIES"]) && $arResult['CATEGORIES_ITEMS_EXISTS']):?>
	<table class="title-search-result">
		<?foreach($arResult["CATEGORIES"] as $category_id => $arCategory): ?>
			<tr>
                <td class="title-search-separator">&nbsp</td>
                <td class="title-search-separator">&nbsp</td>
                <td class="title-search-separator">&nbsp</td>
			</tr>
			<?foreach($arCategory["ITEMS"] as $i => $arItem):?>
            <? if(!$arItem['SEPARATOR']):?>
                <tr>
                    <td><img src="<?echo $arItem["PICTURE"]?>"></td>
                    <td class="title-search-item">
                        <a href="<?echo $arItem["URL"]?>">
                            <?echo $arItem["NAME"]?>
                        </a>
                    </td>
                    <td><strong><?echo $arItem["FORMAT_INT"]?></strong></td>
                </tr>
            <? else:?>
                <tr>
                    <td class="title-search-separator">&nbsp</td>
                    <? if($arItem["NAME"]):?>
                        <td class="title-search-item">&nbsp&nbsp<?=$arItem["NAME"]?></td>
                    <? else: ?>
                        <td class="title-search-separator"><hr/></td>
                    <? endif; ?>
                    <td class="title-search-separator">&nbsp</td>
                </tr>
            <?endif;?>
			<?endforeach;?>
		<?endforeach;?>
	</table>
    <div class="title-search-fader"></div>
<?endif;
?>
