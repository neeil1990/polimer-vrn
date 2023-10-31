<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
if($arResult['ITEMS'])
{
	foreach($arResult['ITEMS'] as $Item)
	{
        if($Item['TITLE'] && $Item['URL']) {
            $count = $arParams['PRODUCT_COUNT'] == 'Y' ? ' (' . $Item['PRODUCT_COUNT'] . ')' : '';
            ?>
            <div class="sotbit-seometa-tags-wrapper">
                <div class="sotbit-seometa-tag">
                    <a class="sotbit-seometa-tag-link" href="<?=$Item['URL'] ?>" title="<?=$Item['TITLE'] . $count ?>"><?=$Item['TITLE'] . $count?></a>
                </div>
            </div>
		    <?
        }
	}
}
?>