<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Import\Table\TreeTable;
use Darneo\Ozon\Main\Table\TreeDisableTable;

class OzonSettingsCategoryListComponent extends CBitrixComponent
{
    private const CATEGORY_LEVEL_1 = 1;
    private const CATEGORY_LEVEL_2 = 2;
    private const CATEGORY_LEVEL_3 = 3;
    private static array $moduleNames = ['darneo.ozon'];
    private int $level1;
    private int $level2;
    private int $level3;
    private array $iblockName = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'group_disable':
                    $categoryIds = $this->request['categoryIds'] ?: [];
                    foreach ($categoryIds as $categoryId) {
                        $this->setActive($categoryId, false);
                    }
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'group_active':
                    $categoryIds = $this->request['categoryIds'] ?: [];
                    foreach ($categoryIds as $categoryId) {
                        $this->setActive($categoryId, true);
                    }
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'disable':
                    $categoryId = $this->request['categoryId'] ?: 0;
                    $value = (bool)$this->request['value'];
                    $this->setActive($categoryId, $value);
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'tree':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                default:
                    $this->setTemplateData();
                    $this->includeComponentTemplate();
            }
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }

        return $result;
    }

    private function loadModules(): void
    {
        foreach (self::$moduleNames as $moduleName) {
            $moduleLoaded = Loader::includeModule($moduleName);
            if (!$moduleLoaded) {
                throw new LoaderException(
                    Loc::getMessage('DARNEO_OZON_MODULE_LOAD_ERROR', ['#MODULE_NAME#' => $moduleName])
                );
            }
        }
    }

    private function dataManager(): void
    {
        $this->level1 = $this->request['level1'] ?: 0;
        $this->level2 = $this->request['level2'] ?: 0;
        $this->level3 = $this->request['level3'] ?: 0;
        $this->level3 = $this->isExistLevel3($this->level3) ? $this->level3 : 0;
        $this->iblockName = $this->getIblockName();
    }

    private function isExistLevel3(int $level3): bool
    {
        $parameters = [
            'select' => ['CATEGORY_ID'],
            'filter' => ['LEVEL' => self::CATEGORY_LEVEL_3, 'CATEGORY_ID' => $level3]
        ];
        $result = TreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            return true;
        }
        return false;
    }

    private function getIblockName(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'NAME']
        ];
        $result = IblockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = $row['NAME'];
        }

        return $rows;
    }

    private function setActive(int $categoryId, bool $active): void
    {
        $category = TreeTable::getById($categoryId)->fetch();
        if ($category) {
            $categoryDisable = TreeDisableTable::getById($category['CATEGORY_ID'])->fetch();
            if ($categoryDisable) {
                TreeDisableTable::update($category['CATEGORY_ID'], ['DISABLE' => !$active]);
            } else {
                TreeDisableTable::add(['CATEGORY_ID' => $category['CATEGORY_ID'], 'DISABLE' => !$active]);
            }
        }
    }

    private function setTemplateData(): void
    {
        $this->arResult['DATA_VUE'] = [
            'TREE' => $this->getTree(),
            'SELECTED' => [
                'LEVEL_1' => $this->level1,
                'LEVEL_2' => $this->level2,
                'LEVEL_3' => $this->level3,
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['PATH_TO_AJAX_IMPORT'] = $this->getPath() . '/ajax_import.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.settings.category.list'
        );
    }

    private function getTree(): array
    {
        $treeList = $this->getTreeLevel(self::CATEGORY_LEVEL_1);
        $level2 = $this->level1 ? $this->getTreeLevel(self::CATEGORY_LEVEL_2, $this->level1) : [];
        $level3 = $this->level2 ? $this->getTreeLevel(self::CATEGORY_LEVEL_3, $this->level2) : [];

        $section = [];
        if ($level3) {
            $categoryLevel3 = [];
            foreach ($level3 as $item) {
                $categoryLevel3[] = $item['CATEGORY_ID'];
            }
            if ($categoryLevel3) {
                $sectionCategoryIds = $this->getSectionLevel3($categoryLevel3);
                foreach ($sectionCategoryIds as $categoryId => $sectionIds) {
                    foreach ($sectionIds as $sectionId) {
                        $section[$categoryId][] = $this->getElementNavChain($sectionId);
                    }
                }
            }
        }

        foreach ($level3 as $key => $item) {
            $level3[$key]['SECTIONS'] = $section[$item['CATEGORY_ID']] ?: [];
        }

        return [
            'LEVEL_1' => $treeList,
            'LEVEL_2' => $level2,
            'LEVEL_3' => $level3
        ];
    }

    private function getTreeLevel(int $level, int $parentId = 0): array
    {
        $rows = [];

        $parameters = [
            'select' => ['CATEGORY_ID', 'PARENT_ID', 'TITLE', 'DISABLE' => 'ACTIVE.DISABLE'],
            'filter' => ['LEVEL' => $level],
            'order' => ['TITLE' => 'ASC'],
        ];

        if ($parentId) {
            $parameters['filter']['PARENT_ID'] = $parentId;
        }

        $result = TreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $isActive = $parentId === (int)$row['CATEGORY_ID'];
            $rows[] = [
                'CATEGORY_ID' => (int)$row['CATEGORY_ID'],
                'PARENT_ID' => (int)$row['PARENT_ID'],
                'TITLE' => $row['TITLE'],
                'SELECTED' => $isActive,
                'DISABLE' => $row['DISABLE']
            ];
        }

        return $rows;
    }

    private function getSectionLevel3(array $level3): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'CATEGORY_ID' => $level3
            ],
            'select' => ['CATEGORY_ID', 'SECTION_ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['CATEGORY_ID']][] = $row['SECTION_ID'];
        }

        return $rows;
    }

    private function getElementNavChain(int $sectionId): array
    {
        $rows = [];
        $iblockId = '';
        $result = CIBlockSection::GetNavChain(false, $sectionId);
        while ($row = $result->GetNext()) {
            $iblockId = $row['IBLOCK_ID'];
            $rows[] = $row['NAME'];
        }
        $str = implode(' -> ', $rows);
        $iblockName = $this->iblockName[$iblockId];

        return [
            'SECTION_ID' => $sectionId,
            'IBLOCK_ID' => $iblockId,
            'IBLOCK_NAME' => $iblockName,
            'TITLE' => $str
        ];
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
