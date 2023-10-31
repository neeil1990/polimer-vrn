<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
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

<? if (!empty($arResult)): ?>
    <ul class='nav nav-pills m-b-30'>
        <? foreach ($arResult as $item): ?>
            <? $classActive = $item['SELECTED'] ? ' active' : '' ?>
            <li class='nav-item'>
                <a class='nav-link<?= $classActive ?>' href='<?= $item['LINK'] ?>'><?= $item['TEXT'] ?></a>
            </li>
        <? endforeach; ?>
    </ul>
<? endif ?>