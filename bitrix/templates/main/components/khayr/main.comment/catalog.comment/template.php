<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeTemplateLangFile(__FILE__);
//$($USER->IsAdmin()) { echo "<pre>"; print_r($arParams); print_r($arResult); echo "</pre>"; die(); }

$APPLICATION->SetAdditionalCSS("/bitrix/modules/parnas.khayrcomment/libs/rateit.js/1.0.23/rateit.css");
$APPLICATION->AddHeadScript("/bitrix/modules/parnas.khayrcomment/libs/rateit.js/1.0.23/jquery.rateit.js");

function KHAYR_MAIN_COMMENT_ShowTree($arItem, $arParams, $arResult)
{
	?>
    <div class="row">
        <div class="col-md-12">
            <div class="item">
                <span class="label"><?=$arItem["AUTHOR"]["FULL_NAME"]?></span>
                <span class="value">
                    <?if ($arItem["MARK"]) {?>
                        <div class="rates" id="rate_<?=$arItem["ID"]?>_result"></div>
                        <script type="text/javascript">
						$(function() {
                            $('#rate_<?=$arItem["ID"]?>_result').rateit({ value: <?=$arItem["MARK"]?>, ispreset: true, readonly: true });
                        });
					</script>
                    <?}?>
                </span>
            </div>
            <?
            if (!empty($arItem["ADDITIONAL"]))
            {
                foreach ($arItem["ADDITIONAL"] as $addit => $val)
                {
                    ?>
                    <div class="item">
                    <?
                    if (!empty($addit) && !empty($val)) {
                        ?>
                        <span class="label"><?=$addit?>:</span>
                        <span class="value"><?=$val?></span>
                        <?
                    }
                    ?>
                    </div>
                <?
                }
            }
            ?>
            <?if ($arItem["DIGNITY"]) {?>
            <div class="item">
                <span class="label"><?=GetMessage("KHAYR_MAIN_COMMENT_DIGNITY")?>:</span>
                <span class="value"><?=$arItem["DIGNITY"]?></span>
            </div>
            <?}?>

            <?if ($arItem["FAULT"]) {?>
                <div class="item">
                    <span class="label"><?=GetMessage("KHAYR_MAIN_COMMENT_FAULT")?>:</span>
                    <span class="value"><?=$arItem["FAULT"]?></span>
                </div>
            <?}?>

            <div class="item">
                <div class="label">Комментарий:</div>
                <span class="value"><?=$arItem["PUBLISH_TEXT"]?></span>
            </div>

            <div class="item">
                <div class="date"><?=$arItem["PUBLISH_DATE"]?></div>
            </div>

        </div>

        <?if (!empty($arItem["CHILDS"])) {?>
            <?foreach ($arItem["CHILDS"] as $item) {?>
                <?=KHAYR_MAIN_COMMENT_ShowTree($item, $arParams, $arResult)?>
            <?}?>
        <?}?>
    </div>
<?
}
?>
<div class='khayr_main_comment' id='KHAYR_MAIN_COMMENT_container'>
	<?if (strlen($_POST["ACTION"]) > 0) $GLOBALS["APPLICATION"]->RestartBuffer();?>

	<p style='color: green; display: none;' class='suc'><?=$arResult["SUCCESS"]?></p>
	<p style='color: red; display: none;' class='err'><?=$arResult["ERROR_MESSAGE"]?></p>

	<?if ($arResult["ITEMS"]) {?>

		<div class="comments">
			<?
			foreach ($arResult["ITEMS"] as $k => $arItem)
			{
				echo KHAYR_MAIN_COMMENT_ShowTree($arItem, $arParams, $arResult);
			}
			?>
		</div>
		<?if ($arParams["DISPLAY_BOTTOM_PAGER"]) {?>
			<div class="nav"><?=$arResult["NAV_STRING"]?></div>
		<?}?>
	<?}?>

    <div class="form comment main_form"<?=($arResult["POST"]["PARENT"] > 0 && !$arResult["SUCCESS"] ? " style='display: none;' " : "")?>>
        <div class="form-header">
            <span class="title"><?=GetMessage('KHAYR_MAIN_COMMENT_ADD_NAME')?></span>
        </div>
        <?if ($arResult["CAN_COMMENT"]) {?>
            <form enctype="multipart/form-data" action="<?=$GLOBALS["APPLICATION"]->GetCurUri()?>" method='POST' onsubmit='return KHAYR_MAIN_COMMENT_validate(this);'>

                <?if ($arParams["LOAD_MARK"]) {?>
                    <input type="hidden" name="MARK" value="0" id="rate_0" />
                    <div class="line cl">
                        <div class="cl_col">
                            <div class="label">
                                <?=GetMessage("KHAYR_MAIN_COMMENT_MARK")?>
                            </div>
                            <div class="value">
                                <div class="rates" id="rate_0_control"></div>
                            </div>
                        </div>
                    </div>
                    <script type="text/javascript">
                        $(function() {
                            $('#rate_0_control').rateit({ min: 0, max: 5, step: 1, backingfld: '#rate_0', resetable: false });
                        });
                    </script>
                <?}?>

                <div class="line cl">
                    <div class="cl_col w-50">
                        <div class="label">
                            <?=GetMessage("KHAYR_MAIN_COMMENT_FNAME")?>
                        </div>
                        <div class="value">
                            <input type="text" name='NONUSER' value='<?=$arResult["POST"]["NONUSER"]?>' <?=($arResult["USER"]["ID"] ? "readonly='readonly'" : "")?> placeholder="<?=GetMessage("KHAYR_MAIN_COMMENT_FNAME")?>"/>
                        </div>
                    </div>
                    <? if ($arResult["LOAD_EMAIL"]) {?>
                        <div class="cl_col w-50">
                            <div class="label">
                                <?=GetMessage("KHAYR_MAIN_COMMENT_EMAIL")?>
                            </div>
                            <div class="value">
                                <input type="text" name='EMAIL' <?=($arResult["USER"]["ID"] ? "value='".$arResult["USER"]["EMAIL"]."' readonly='readonly'" : "value='".$arResult["POST"]["EMAIL"]."'")?> placeholder="<?=GetMessage("KHAYR_MAIN_COMMENT_EMAIL")?>" />
                            </div>
                        </div>
                    <?}?>
                </div>

                <?
                $arRadio = ['mm_radio' => 'Менее месяца','bm_radio' => 'Более месяца', 'bg_radio' => 'Более года'];
                foreach ($arParams["ADDITIONAL"] as $additional) {?>
                    <div class="line cl">
                        <div class="cl_col">
                            <div class="label">
                                <?=$additional?>
                            </div>
                            <div class="value">
                                <? foreach ($arRadio as $id => $r): ?>
                                <div class="btn_radio">
                                    <input type="radio" id="<?=$id?>" name='<?=urlencode($additional)?>' value='<?=$r?>' <? if(key($arRadio) == $id):?>checked<?endif;?>/>
                                    <label for="<?=$id?>"><?=$r?></label>
                                </div>
                                <?endforeach;?>
                            </div>
                        </div>
                    </div>
                <?}?>


                <?if ($arParams["LOAD_DIGNITY"]) {?>
                    <div class="line cl">
                        <div class="cl_col">
                            <div class="label">
                                <?=GetMessage("KHAYR_MAIN_COMMENT_DIGNITY")?>
                            </div>
                            <div class="value">
                                <input type="text" name="DIGNITY" value="<?=$arResult["POST"]["DIGNITY"]?>" placeholder="<?=GetMessage("KHAYR_MAIN_COMMENT_DIGNITY")?>" />
                            </div>
                        </div>
                    </div>
                <?}?>

                <?if ($arParams["LOAD_FAULT"]) {?>
                    <div class="line cl">
                        <div class="cl_col">
                            <div class="label">
                                <?=GetMessage("KHAYR_MAIN_COMMENT_FAULT")?>
                            </div>
                            <div class="value">
                                <input type="text" name="FAULT" value="<?=$arResult["POST"]["FAULT"]?>" placeholder="<?=GetMessage("KHAYR_MAIN_COMMENT_FAULT")?>" />
                            </div>
                        </div>
                    </div>
                <?}?>
                <div class="line cl">
                    <div class="cl_col">
                        <div class="label">
                            <?=GetMessage("KHAYR_MAIN_COMMENT_MESSAGE")?>
                        </div>
                        <div class="value">
                            <textarea name="MESSAGE" rows="10" placeholder='<?=GetMessage("KHAYR_MAIN_COMMENT_MESSAGE")?>'><?=$arResult["POST"]["MESSAGE"]?></textarea>
                        </div>
                    </div>
                </div>

                <input type='hidden' name='PARENT' value='' />
                <input type='hidden' name='ACTION' value='add' />
                <input type='hidden' name='DEPTH' value='1' />
                <?if ($arParams["USE_CAPTCHA"]) {?>
                    <div class="line cl">
                        <div class="cl_col">
                            <div class="value">
                                <div class="mf-captcha">
                                    <div class="g-recaptcha" id="KHAYR_MAIN_COMMENT_grecaptcha" data-sitekey="6LfZ8kgUAAAAAJWtIx1_4_pUvd1Xk_VfdMhpqT4P"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?}?>
                <?if ($arParams["LEGAL"]) {?>
                    <input type='checkbox' id="LEGAL_main_form" name='LEGAL' value='Y' <?=($arResult["POST"]["LEGAL"] == "Y" ? "checked" : "")?> />
                    <label for="LEGAL_main_form"><?=$arParams["LEGAL_TEXT"]?></label>
                    <div class="clear pt10"></div>
                <?}?>
                <div class="line cl">
                    <div class="cl_col">
                        <input type="submit" value="<?=GetMessage("KHAYR_MAIN_COMMENT_ADD")?>" />
                    </div>
                </div>

            </form>
        <?} else {?>
            <div style='background: #FFFFFF;'>
                <?=GetMessage("KHAYR_MAIN_COMMENT_DO_AUTH", array("#LINK#" => $arParams["AUTH_PATH"]))?>
            </div>
        <?}?>
    </div>
	<?if (strlen($_POST["ACTION"]) > 0) die();?>
</div>