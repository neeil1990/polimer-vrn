<?php

namespace Sotbit\Seometa\Helper\ImportExport;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\ParseResultTable;
use Sotbit\Seometa\Orm\SectionUrlTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapSectionTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sotbit.seometa/vendor/autoload.php");

class ImportHelper
{
    const ID_MODULE = 'sotbit.seometa';

    public static function getCHPUFields()
    {
        return [
            'ACTIVE',
            'NAME',
            'SORT',
            'REAL_URL',
            'NEW_URL',
            'SITE',
            'ELEMENT_TITLE',
            'ELEMENT_KEYWORDS',
            'ELEMENT_DESCRIPTION',
            'ELEMENT_PAGE_TITLE',
            'ELEMENT_BREADCRUMB_TITLE',
            'ELEMENT_TOP_DESC',
            'ELEMENT_BOTTOM_DESC',
            'ELEMENT_ADD_DESC',
            'ELEMENT_FILE',
        ];
    }

    public static function getConditionFields()
    {
        return [
            'ACTIVE',
            'SEARCH',
            'SORT',
            'NO_INDEX',
            'STRONG',
            'NAME',
            'SITES',
            'TYPE_OF_INFOBLOCK',
            'INFOBLOCK',
            'SECTIONS',
            'RULE',
            'FILTER_TYPE',
            'PRIORITY',
            'CHANGEFREQ',
            'ELEMENT_TITLE',
            'ELEMENT_KEYWORDS',
            'ELEMENT_DESCRIPTION',
            'ELEMENT_PAGE_TITLE',
            'ELEMENT_BREADCRUMB_TITLE',
            'ELEMENT_TOP_DESC',
            'ELEMENT_BOTTOM_DESC',
            'ELEMENT_ADD_DESC',
            'ELEMENT_FILE',
            'TAG',
            'HIDE_IN_SECTION',
            'TEMPLATE_NEW_URL',
            'SPACE_REPLACEMENT',
            'GENERATE_AJAX',
        ];
    }

    public static function getColumn(int $fileID)
    {
        $file = \CFile::GetByID($fileID)->fetch() ?: null;

        if ($file === null) {
            return $file;
        }

        $fileSrc = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        $tmpDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $file['SUBDIR'];

        if (pathinfo($fileSrc)['extension'] === 'xlsx') {
            $zip = new \ZipArchive();
            $zip->open($fileSrc);
            $zip->extractTo($tmpDir);

            $strings = simplexml_load_file($tmpDir . '/xl/sharedStrings.xml');
            $sheet = simplexml_load_file($tmpDir . '/xl/worksheets/sheet1.xml');

            $xlrows = $sheet->sheetData->row;
            $totalCount = is_countable($sheet->sheetData->row) ? count($sheet->sheetData->row) : 0;
            foreach ($xlrows as $xlrow) {

                // In each row, grab it's value
                foreach ($xlrow->c as $cell) {
                    $v = (string)$cell->v;

                    // If it has a "t" (type?) of "s" (string?), use the value to look up string value
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $s = array();
                        $si = $strings->si[(int)$v];

                        // Register & alias the default namespace or you'll get empty results in the xpath query
                        $si->registerXPathNamespace('n', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                        // Cat together all of the 't' (text?) node values
                        foreach ($si->xpath('.//n:t') as $t) {
                            $s[] = (string)$t;
                        }

                        $v = implode($s);
                    }
                    $cellid = preg_replace('/[^A-Z]/i', '', $cell['r']);
                    $sheetData[$cellid] = $v;
                }

                break;
            }
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fileSrc);
            $reader->setReadDataOnly(true);
            /*$chunkFilter = new ChunkReadFilter();
            $reader->setReadFilter($chunkFilter);
            $chunkFilter->setRows(1, 1);*/

            $mySpreadsheet = $reader->load($fileSrc);
            $worksheet = $mySpreadsheet->getSheet(0);
            $totalCount = $worksheet->getHighestDataRow();
            $sheetData = [];
            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                foreach ($cellIterator as $cell) {
                    if ($cell->getValue() !== null) {
                        $sheetData[$cell->getColumn()] = Encoding::convertEncodingToCurrent($cell->getValue());
                    }
                }
            }
        }

        $sheetData = $sheetData ?: null;
        $totalCount = $totalCount ?: 0;

        return [$sheetData, $totalCount];
    }

    public static function columnForSelect(array $arColumn)
    {
        $collectionColumnsForModules['REFERENCE_ID'][0] = 0;
        $collectionColumnsForModules['REFERENCE'][0] = Loc::getMessage(self::ID_MODULE . "_CHOOSE");

        foreach ($arColumn as $key => $dictionary) {
            $collectionColumnsForModules['REFERENCE_ID'][] = $key;
            $collectionColumnsForModules['REFERENCE'][] = $dictionary;
        }

        return $collectionColumnsForModules;
    }

    public static function categoryForSelect(?string $entity)
    {
        $arFields = [];
        if ($entity === 'cond') {
            $arFields = SitemapSectionTable::query()
                ->setSelect(['ID', 'NAME'])
                ->setFilter([])
                ->fetchAll();
        } else {
            $arFields = SectionUrlTable::query()
                ->setSelect(['ID', 'NAME'])
                ->setFilter([])
                ->fetchAll();
        }

        $collectionCategory['REFERENCE_ID'][0] = 0;
        $collectionCategory['REFERENCE'][0] = Loc::getMessage(self::ID_MODULE . "_CHOOSE_CATEGORY");

        foreach ($arFields as $field){
            $collectionCategory['REFERENCE_ID'][] = $field['ID'];
            $collectionCategory['REFERENCE'][] = $field['NAME'];
        }

        return $collectionCategory;
    }

    public static function parseCHPUFromExcelFile($file, $requestValues)
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/interface/admin_lib.php");

        $mySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_SERVER['DOCUMENT_ROOT'] . $file['SRC']);
        $worksheet = $mySpreadsheet->getSheet(0);
        list($offset, $limit, $currentCount, $totalCount) = [
            $requestValues['offset'],
            $requestValues['limit'],
            $requestValues['currentCount'],
            $requestValues['totalCount']
        ];
        if ($requestValues['firstCheck'] == 'true') {
            $totalCount = $currentCount = $totalCount - $requestValues['DATA_ROW'] + 1;
        }
        if ($totalCount === 0) {
            $result['PROGRESSBAR'] = (new \CAdminMessage(
                [
                    "TYPE" => "ERROR",
                    "MESSAGE" => Loc::getMessage('SEO_META_RUN_ERROR'),
                    "DETAILS" => Loc::getMessage('SEO_META_RUN_ERROR_DETAILS'),
                ]
            ))->show();
            $result['ERROR'] = 1;
            return $result;
        }
        $offset = $offset ?: $offset + $requestValues['DATA_ROW'];
        $limit = $offset + $limit - 1;

        $arFields['CATEGORY_ID'] = $requestValues['CATEGORY_ID'];

        foreach ($requestValues as $key => $value) {
            if (!in_array($key, self::getCHPUFields()) || $key === 'SITE') {
                if ($key === 'SITE') {
                    $requestValues['SITE_ID'] = $value;
                }
                unset($requestValues[$key]);
            }
        }

        $rowCount = 0;
        foreach ($worksheet->getRowIterator($offset, $limit) as $row) {
            if (($currentCount - $rowCount) == 0) {
                break;
            }

            if($row->isEmpty(\PhpOffice\PhpSpreadsheet\Worksheet\CellIterator::TREAT_NULL_VALUE_AS_EMPTY_CELL)){
                $rowCount++;
                continue;
            }

            foreach ($requestValues as $key => $value) {
                if ($value === "0") {
                    $arFields[$key] = null;
                } else {
                    $arFields[$key] = Encoding::convertEncodingToCurrent($worksheet->getCell($value . $row->getRowIndex())->getValue());
                }
            }

            $resultAdd = SeometaUrlTable::addAfterParse($arFields);
            if ($resultAdd !== null) {
                $resultParse = [
                    'FILE_ID' => $file['ID'],
                    'ENTITY_ROW' => $row->getRowIndex(),
                    'ENTITY_NAME' => $arFields['NAME'],
                    'MESSAGE' => $resultAdd
                ];
                ParseResultTable::add($resultParse);
            }
            $rowCount++;
        }
        if (($countRes = $currentCount - $rowCount) > 0) {
            $result['COUNT'] = $countRes;
            $result['OFFSET'] = $offset + $rowCount;
            $result['TOTAL_COUNT'] = $totalCount;
            $curPercent = 100 - intdiv((100 * $countRes), $result['TOTAL_COUNT']);
            $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), $curPercent);
        } elseif (($currentCount - $rowCount) == 0) {
            $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_RUN_FINISH'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), 100);
        }
        return $result;
    }

    public static function parseConditionFromExcelFile($file, $requestValues)
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/interface/admin_lib.php");

        $generate = $requestValues['GENERATE_CHPU'];

        list($offset, $limit, $currentCount, $totalCount) = [
            $requestValues['offset'],
            $requestValues['limit'],
            $requestValues['currentCount'],
            $requestValues['totalCount']
        ];

        $offset = $offset ?: $offset + $requestValues['DATA_ROW'];
        $limit = $offset + $limit - 1;

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst(pathinfo($file['SRC'])['extension']));
        $reader->setReadDataOnly(true);
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $chunkFilter->setRows($offset, $limit);

        $mySpreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . $file['SRC']);
        $worksheet = $mySpreadsheet->getSheet(0);

        if ($requestValues['firstCheck'] == 'true') {
            $totalCount = $currentCount = $totalCount - $requestValues['DATA_ROW'] + 1;
        }
        if ($totalCount === 0) {
            $result['PROGRESSBAR'] = (new \CAdminMessage(
                [
                    "TYPE" => "ERROR",
                    "MESSAGE" => Loc::getMessage('SEO_META_RUN_ERROR'),
                    "DETAILS" => Loc::getMessage('SEO_META_RUN_ERROR_DETAILS'),
                ]
            ))->show();
            $result['ERROR'] = 1;
            return $result;
        } elseif ($totalCount < 0) {
            $result['PROGRESSBAR'] = (new \CAdminMessage(
                [
                    "TYPE" => "ERROR",
                    "MESSAGE" => Loc::getMessage('SEO_META_RUN_ERROR'),
                    "DETAILS" => Loc::getMessage('SEO_META_RUN_ERROR_DETAILS_ROW'),
                ]
            ))->show();
            $result['ERROR'] = 1;
            return $result;
        }

        $arFields['CATEGORY_ID'] = $requestValues['CATEGORY_ID'];

        foreach ($requestValues as $key => $value) {
            if (!in_array($key, self::getConditionFields())) {
                unset($requestValues[$key]);
            }
        }

        $rowCount = 0;
        foreach ($worksheet->getRowIterator($offset, $limit) as $row) {
            if (($currentCount - $rowCount) == 0) {
                break;
            }

            if($row->isEmpty(\PhpOffice\PhpSpreadsheet\Worksheet\CellIterator::TREAT_NULL_VALUE_AS_EMPTY_CELL)){
                $rowCount++;
                continue;
            }

            foreach ($requestValues as $key => $value) {
                if ($value === "0") {
                    $arFields[$key] = null;
                } else {
                    $arFields[$key] = Encoding::convertEncodingToCurrent($worksheet->getCell($value . $row->getRowIndex())->getValue());
                }
            }

            $resultAdd = ConditionTable::addAfterParse($arFields, $generate);
            if ($resultAdd !== null) {
                $resultParse = [
                    'FILE_ID' => $file['ID'],
                    'ENTITY_ROW' => $row->getRowIndex(),
                    'ENTITY_NAME' => $arFields['NAME'],
                    'MESSAGE' => $resultAdd
                ];
                ParseResultTable::add($resultParse);
            }
            $rowCount++;
        }
        if (($countRes = $currentCount - $rowCount) > 0) {
            $result['COUNT'] = $countRes;
            $result['OFFSET'] = $offset + $rowCount;
            $result['TOTAL_COUNT'] = $totalCount;
            $curPercent = 100 - intdiv((100 * $countRes), $result['TOTAL_COUNT']);
            $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), $curPercent);
        } elseif (($currentCount - $rowCount) == 0) {
            $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_RUN_FINISH'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), 100);
        }
        return $result;
    }

    public static function createArrayForTree($arrStr, $result, $parent = 0, &$i = 0, &$id = 1)
    {
        //need for exit from loop while
        $flag = true;
        $resString = '';
        while ($flag) {
            //exit from while, if index more than array count
            if ($i > count($arrStr) - 1) {
                break;
            }

            //create CondGroup array, and recycle func for create nested rule
            if ($arrStr[$i] === '[') {
                $result[$id] = ['id' => $id, 'parent' => $parent, 'CLASS_ID' => 'CondGroup', 'DATA' => ['All' => '', 'True' => 'True']];
                $nextParent = $id;
                $i++;
                $id++;
                //$i and $id transmitted with link, because we always need actual $arrStr index and $result id
                $result = self::createArrayForTree($arrStr, $result, $nextParent, $i, $id);
                //'continue;' need for skip next code fragments, because if we didn't skip, current index $arrStr will be wrong
                continue;
            } elseif ($arrStr[$i] === ']') {
                //close CondGroup, exit from while in parent rule and return nested rule
                $flag = false;
                //need for write final rule, which not CondGroup
                $writeFlag = true;
            }

            //write all chars, except '[]'
            if (!$writeFlag) {
                $resString .= $arrStr[$i];
            }

            //if '{AND}' and '{OR}' meet first, we must write them to parent rule
            if ($resString === '{AND}' || $resString === '{OR}') {
                $result[$parent]['DATA']['All'] = trim($resString, '{}');
                $resString = '';
            }
            //in this if, we parse final rule, which not CondGroup
            if ($writeFlag && $resString) {
                $sep = '{AND}';
                $explodeResString = explode($sep, $resString);

                if (count($explodeResString) <= 1) {
                    $sep = '{OR}';
                    $explodeResString = explode($sep, $resString);
                }

                //if rule haven't '{AND}' and '{OR}', default should be '{AND}'
                if (count($explodeResString) === 1) {
                    $sep = '{AND}';
                }

                foreach ($explodeResString as $item) {
                    if(empty($item)){
                        continue;
                    }
                    $explodeItem = explode(':', $item);
                    if (is_countable($explodeItem) && count($explodeItem) > 3) {
                        //if from excel arrive symb CODE
                        if (!is_numeric($explodeItem[1])) {
                            $explodeItem[1] = IblockTable::query()
                                ->addSelect('ID')
                                ->addFilter('CODE', $explodeItem[1])
                                ->fetch()['ID'];
                        }
                        if (!is_numeric($explodeItem[2])) {
                            $explodeItem[2] = PropertyTable::query()
                                ->setFilter(['IBLOCK_ID' => $explodeItem[1], 'CODE' => strtoupper($explodeItem[2])])
                                ->addSelect('ID')
                                ->fetch()['ID'];
                        }
                        $classID = $explodeItem[0] . ':' . $explodeItem[1] . ':' . $explodeItem[2];
                        $logic = $explodeItem[3];
                        $value = $explodeItem[4] ?: '';
                    } else {
                        $classID = $explodeItem[0];
                        $logic = $explodeItem[1];
                        $value = $explodeItem[2] ?: '';
                    }
                    $result[$parent]['DATA']['All'] = trim($sep, '{}');
                    $result[$id] = ['id' => $id, 'parent' => $parent, 'CLASS_ID' => $classID, 'DATA' => ['logic' => $logic, 'value' => $value]];
                    $id++;
                }
            }
            $i++;
        }
        return $result;
    }

    //example
    //https://phpclub.ru/talk/threads/%D0%9A%D0%B0%D0%BA-%D0%BF%D0%BE%D1%81%D1%82%D1%80%D0%BE%D0%B8%D1%82%D1%8C-%D0%B4%D0%B5%D1%80%D0%B5%D0%B2%D0%BE-%D0%B8%D0%B7-%D0%BC%D0%B0%D1%81%D1%81%D0%B8%D0%B2%D0%B0.44320/post-376804
    public static function createTree($array)
    {
        $tree = [];
        $sub = [0 => &$tree];

        foreach ($array as $item) {
            $id = $item['id'];
            $parent = $item['parent'];
            $CLASS_ID = $item['CLASS_ID'];
            $DATA = $item['DATA'];

            $branch = &$sub[$parent];
            if ($parent === 0) {
                $branch[$id] = ['CLASS_ID' => $CLASS_ID, 'DATA' => $DATA];
                $sub[$id] = &$branch[$id];
            } else {
                $branch['CHILDREN'][$id] = ['CLASS_ID' => $CLASS_ID, 'DATA' => $DATA];
                $sub[$id] = &$branch['CHILDREN'][$id];
            }
        }

        return $tree[1];
    }

    public static function morphyRuleForImport($rule)
    {
        $result = [];
        $rule = str_split($rule);
        $result = self::createArrayForTree($rule, $result);
        $result = self::createTree($result);
        return serialize($result);
    }
}