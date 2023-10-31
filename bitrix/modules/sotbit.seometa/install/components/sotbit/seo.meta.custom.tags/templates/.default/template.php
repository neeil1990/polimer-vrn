<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
if ($arResult['ITEMS']):?>
    <div class="sotbit-seometa-tags-column">
        <? if(is_array($arResult['ITEMS'])){ ?>
            <div class="sotbit-seometa-tags-column-container">
                <? foreach ($arResult['ITEMS'] as $item){
                    if($item['IMAGE']['SRC']){ ?>
                        <a class="seometa__item" href="<?= $item['URL'] ?>" <?= $item['TITLE'] ? "title=\"". $item['TITLE'] .'"' : '' ?>>
                            <div class="seometa__img-wrapper">
                                <img class="seometa__img"
                                     src="<?= $item['IMAGE']['SRC'] ?>"
                                     alt="<?= $item['IMAGE']['SRC'] ?>"
                                    <?= $item['TITLE'] ? "title=\"". $item['TITLE'] .'"' : '' ?>>
                            </div>
                            <?if($item['TITLE']){?>
                                <p class="seometa__title"><?= $item['TITLE'] ?></p>
                            <?}?>
                        </a>
                    <? }elseif($item['TITLE'] && $item['URL']){ ?>
                        <div class="tags_wrapper">
                            <div class="tags_section">
                                <div class="sotbit-seometa-tags-column-wrapper">
                                    <div class="sotbit-seometa-tag-column">
                                        <a class="sotbit-seometa-tag-link" href="<?= $item['URL'] ?>"
                                           title="<?= $item['TITLE'] ?>"><?= $item['TITLE'] ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?  }
                } ?>
            </div>
        <? } ?>
    </div>
<?endif;?>
