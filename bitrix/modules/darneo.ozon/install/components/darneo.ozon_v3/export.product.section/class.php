<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Import\Table\TreeTable;
use Darneo\Ozon\Main\Table\TreeDisableTable;

class OzonExportProductSectionComponent extends CBitrixComponent
{
    private const CATEGORY_LEVEL_1 = 1;
    private const CATEGORY_LEVEL_2 = 2;
    private const CATEGORY_LEVEL_3 = 3;
    private static array $moduleNames = ['darneo.ozon'];
    private int $iblockId = 0;
    private int $level1;
    private int $level2;
    private int $level3;

    private array $sectionCategory = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                case 'tree':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'setCategory':
                    $iblockId = $this->request['iblockId'];
                    $sectionId = $this->request['sectionId'] ?: 0;
                    $level3 = $this->request['level3'];
                    $status = $this->setCategory($level3, $iblockId, $sectionId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'deleteCategory':
                    $iblockId = $this->request['iblockId'];
                    $sectionId = $this->request['sectionId'] ?: 0;
                    $status = $this->deleteCategory($iblockId, $sectionId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
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
        $this->iblockId = $this->getIblockId($this->arParams['ELEMENT_ID']);
        $this->level1 = $this->request['level1'] ?: 0;
        $this->level2 = $this->request['level2'] ?: 0;
        $this->level3 = $this->request['level3'] ?: 0;
        $this->level3 = $this->isExistLevel3($this->level3) ? $this->level3 : 0;
    }

    private function getIblockId($elementId): int
    {
        $parameters = [
            'filter' => [
                'ID' => $elementId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ProductListTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'] ?: 0;
        }

        return 0;
    }

    private function isExistLevel3(int $level3): bool
    {
        $parameters = [
            'select' => ['CATEGORY_ID'],
            'filter' => ['LEVEL' => self::CATEGORY_LEVEL_3, 'CATEGORY_ID' => $level3],
            'cache' => ['ttl' => 86400]
        ];
        $result = TreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            return true;
        }
        return false;
    }

    private function setTemplateData(): void
    {
        $this->sectionCategory = $this->getSectionCategory();

        $sections = $this->getIblockSection();

        // bind tree
        $sectionTree['ROOT'] = [];
        $tmpSection[0] = &$sectionTree['ROOT'];
        foreach ($sections as $section) {
            $tmpSection[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']] = $section;
            $tmpSection[$section['ID']] = &$tmpSection[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']];
        }
        unset($tmpSection);

        // unset key
        $sectionTree = $this->arrayValuesRecursive($sectionTree['ROOT']) ?: [];
        if ($this->iblockId) {
            $sectionTree['NAME'] = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_SECTION_IBLOCK');
            $sectionTree['IBLOCK_ID'] = $this->iblockId;
            $sectionTree['ID'] = 0;
            $sectionTree['CATEGORY'] = $this->sectionCategory[0]['CATEGORY_NAME'] ?: '';
        }

        $this->arResult['DATA_VUE'] = [
            'FILTER' => [
                'SELECTED' => $this->iblockId
            ],
            'SECTION' => $sectionTree,
            'TREE' => [
                'ITEMS' => $this->getTree(),
                'SELECTED' => [
                    'LEVEL_1' => $this->level1,
                    'LEVEL_2' => $this->level2,
                    'LEVEL_3' => $this->level3,
                ]
            ],
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.section'
        );
    }

    private function getSectionCategory(): array
    {
        if (!$this->iblockId) {
            return [];
        }
        $categoryIds = $this->getConnectionSectionTree();
        if (!$categoryIds) {
            return [];
        }
        $categoryName = $this->getCategoryName($categoryIds);
        $data = [];
        foreach ($categoryIds as $sectionId => $categoryId) {
            $data[$sectionId] = [
                'CATEGORY_ID' => $categoryId,
                'CATEGORY_NAME' => $categoryName[$categoryId],
            ];
        }

        return $data;
    }

    private function getConnectionSectionTree(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IBLOCK_ID' => $this->iblockId],
            'select' => ['ID', 'SECTION_ID', 'CATEGORY_ID'],

        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['SECTION_ID']] = $row['CATEGORY_ID'];
        }

        return $rows;
    }

    private function getCategoryName(array $ids): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['CATEGORY_ID' => $ids],
            'select' => ['CATEGORY_ID', 'TITLE'],

        ];
        $result = TreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['CATEGORY_ID']] = $row['TITLE'];
        }

        return $rows;
    }

    private function getIblockSection(): array
    {
        $rows = [];
        if ($this->iblockId > 0) {
            $parameters = [
                'filter' => [
                    'IBLOCK_ID' => $this->iblockId,
                    'ACTIVE' => 'Y',
                    'GLOBAL_ACTIVE' => 'Y'
                ],
                'select' => ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'IBLOCK_ID'],
                'order' => ['LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC'],
                'cache' => ['ttl' => 86400]
            ];
            $result = SectionTable::getList($parameters);
            while ($row = $result->fetch()) {
                $row['CATEGORY'] = $this->sectionCategory[$row['ID']]['CATEGORY_NAME'] ?: '';
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function arrayValuesRecursive(array $arr): array
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->arrayValuesRecursive($value);
            }
        }

        if (isset($arr['CHILD'])) {
            $arr['CHILD'] = array_values($arr['CHILD']);
        }

        return $arr;
    }

    private function getTree(): array
    {
        $treeList = $this->getTreeLevel(self::CATEGORY_LEVEL_1);
        $level2 = $this->level1 ? $this->getTreeLevel(self::CATEGORY_LEVEL_2, $this->level1) : [];
        $level3 = $this->level2 ? $this->getTreeLevel(self::CATEGORY_LEVEL_3, $this->level2) : [];

        return [
            'LEVEL_1' => $treeList,
            'LEVEL_2' => $level2,
            'LEVEL_3' => $level3
        ];
    }

    private function getTreeLevel(int $level, int $parentId = 0): array
    {
        $rows = [];
        $disable = $this->getTreeDisable();

        $parameters = [
            'select' => ['CATEGORY_ID', 'PARENT_ID', 'TITLE', 'DISABLE' => 'ACTIVE.DISABLE'],
            'filter' => ['LEVEL' => $level, '!=CATEGORY_ID' => $disable],
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

    private function getTreeDisable(): array
    {
        $rows = [];

        $result = TreeDisableTable::getList(
            [
                'filter' => ['DISABLE' => true],
                'select' => ['CATEGORY_ID'],
                'cache' => ['ttl' => 86400]
            ]
        );
        while ($row = $result->fetch()) {
            $rows[] = (int)$row['CATEGORY_ID'];
        }

        return $rows;
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function setCategory(int $level3, int $iblockId, int $sectionId = 0): array
    {
        $params = [
            'IBLOCK_ID' => $iblockId,
            'CATEGORY_ID' => $level3,
        ];
        if ($sectionId) {
            $params['SECTION_ID'] = $sectionId;
        }

        $id = $this->getConnectionTreeId($iblockId, $sectionId);
        if ($id) {
            $result = ConnectionSectionTreeTable::update($id, $params);
        } else {
            $result = ConnectionSectionTreeTable::add($params);
        }
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['ERROR_LIST' => $result->getErrorMessages(), 'STATUS' => 'ERROR'];
    }

    private function getConnectionTreeId(int $iblockId, int $sectionId = 0): int
    {
        $parameters = [
            'filter' =>
                [
                    'IBLOCK_ID' => $iblockId,
                    'SECTION_ID' => $sectionId ?: false
                ],
            'select' => ['ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['ID'];
        }

        return 0;
    }

    private function deleteCategory(int $iblockId, int $sectionId = 0): array
    {
        $parameters = [
            'filter' => ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => $sectionId ?: false],
            'select' => ['ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            $delete = ConnectionSectionTreeTable::delete($row['ID']);
            if ($delete->isSuccess()) {
                return ['STATUS' => 'SUCCESS'];
            }
            return ['ERROR_LIST' => $delete->getErrorMessages(), 'STATUS' => 'ERROR'];
        }

        return ['STATUS' => 'ERROR'];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
