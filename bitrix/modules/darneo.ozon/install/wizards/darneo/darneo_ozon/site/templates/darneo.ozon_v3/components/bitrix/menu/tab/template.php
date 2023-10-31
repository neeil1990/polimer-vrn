<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

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

<?php if (!empty($arResult)): ?>
    <div class='stepper stepper-pills'>
        <div class='stepper-nav flex-start flex-wrap mb-10'>
            <?php
            $i = 0;
            $next = true;
            foreach ($arResult as $key => $arItem):
                $i++;
                if ($arParams['MAX_LEVEL'] == 1 && $arItem['DEPTH_LEVEL'] > 1) {
                    continue;
                }
                if ($arItem['SELECTED']) {
                    $next = false;
                }
                ?>
                <div class='stepper-item mx-8 my-4<?php if ($arItem['SELECTED']): ?> current <?php endif ?><?php if ($next): ?> completed <?php endif ?>'>
                    <a href='<?= $arItem['LINK'] ?>' class='stepper-wrapper d-flex align-items-center'>
                        <div class='stepper-icon w-40px h-40px'>
                            <i class='stepper-check fas fa-check'></i>
                            <span class='stepper-number'><?= $i ?></span>
                        </div>
                        <div class='stepper-label'>
                            <h3 class='stepper-title'>
                                <?= $arItem['TEXT'] ?>
                            </h3>

                            <div class='stepper-desc'>
                                <?= $arItem['PARAMS']['DESC'] ?>
                            </div>
                        </div>
                    </a>
                    <div class='stepper-line h-40px'></div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>