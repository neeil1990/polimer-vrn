<?php

namespace Sotbit\Seometa\Helper\ImportExport;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\SeoMetaMorphy;
use Bitrix\Iblock\Template\Entity\Section;
use Bitrix\Iblock\Template\Engine;

Loc::loadMessages(__FILE__);

class ExportHelper
{
    const MODULE_ID = 'sotbit.seometa';
    const UPLOAD_TEMPLATE_DIR = '/upload/' . self::MODULE_ID . '/';

    public static array $textAreaType = [
        'ELEMENT_TOP_DESC_TYPE',
        'ELEMENT_BOTTOM_DESC_TYPE',
        'ELEMENT_ADD_DESC_TYPE',
    ];

    public static array $skipKey = [
        'section_id',
        'PROPERTIES',
        'RULE',
        'META',
        'CONDITION_META',
        'CHPU_META',
    ];

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
            'META',
            'TAG',
            'HIDE_IN_SECTION',
            'GENERATE_AJAX',
        ];
    }

    public static function getConditionHeaders()
    {
        return [
            'ACTIVE' => '',
            'SEARCH' => '',
            'SORT' => '',
            'NO_INDEX' => '',
            'STRONG' => '',
            'NAME' => '',
            'SITES' => '',
            'TYPE_OF_INFOBLOCK' => '',
            'INFOBLOCK' => '',
            'SECTIONS' => '',
            'RULE' => '',
            'FILTER_TYPE' => '',
            'PRIORITY' => '',
            'CHANGEFREQ' => '',
            'ELEMENT_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_TITLE'),
            'ELEMENT_KEYWORDS' => Loc::getMessage('SEOMETA_ELEMENT_KEYWORDS'),
            'ELEMENT_DESCRIPTION' => Loc::getMessage('SEOMETA_ELEMENT_DESCRIPTION'),
            'ELEMENT_PAGE_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_PAGE_TITLE'),
            'ELEMENT_BREADCRUMB_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_BREADCRUMB_TITLE'),
            'ELEMENT_TOP_DESC' => Loc::getMessage('SEOMETA_ELEMENT_TOP_DESC'),
            'ELEMENT_BOTTOM_DESC' => Loc::getMessage('SEOMETA_ELEMENT_BOTTOM_DESC'),
            'ELEMENT_ADD_DESC' => Loc::getMessage('SEOMETA_ELEMENT_ADD_DESC'),
            'ELEMENT_FILE' => Loc::getMessage('SEOMETA_ELEMENT_FILE'),
            'TAG' => '',
            'HIDE_IN_SECTION' => '',
            'TEMPLATE_NEW_URL' => Loc::getMessage('SEOMETA_TEMPLATE_NEW_URL'),
            'SPACE_REPLACEMENT' => Loc::getMessage('SEOMETA_SPACE_REPLACEMENT'),
            'GENERATE_AJAX' => '',
        ];
    }

    public static function getCHPUFields()
    {
        return [
            'ACTIVE',
            'SORT',
            'NAME',
            'REAL_URL',
            'NEW_URL',
            'SITE_ID',
            'section_id',
            'PROPERTIES'
        ];
    }

    public static function getCHPUHeaders()
    {
        return [
            'ACTIVE' => '',
            'NAME' => '',
            'SORT' => '',
            'REAL_URL' => '',
            'NEW_URL' => '',
            'SITE' => '',
            'ELEMENT_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_TITLE'),
            'ELEMENT_KEYWORDS' => Loc::getMessage('SEOMETA_ELEMENT_KEYWORDS'),
            'ELEMENT_DESCRIPTION' => Loc::getMessage('SEOMETA_ELEMENT_DESCRIPTION'),
            'ELEMENT_PAGE_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_PAGE_TITLE'),
            'ELEMENT_BREADCRUMB_TITLE' => Loc::getMessage('SEOMETA_ELEMENT_BREADCRUMB_TITLE'),
            'ELEMENT_TOP_DESC' => Loc::getMessage('SEOMETA_ELEMENT_TOP_DESC'),
            'ELEMENT_BOTTOM_DESC' => Loc::getMessage('SEOMETA_ELEMENT_BOTTOM_DESC'),
            'ELEMENT_ADD_DESC' => Loc::getMessage('SEOMETA_ELEMENT_ADD_DESC'),
            'ELEMENT_FILE' => Loc::getMessage('SEOMETA_ELEMENT_FILE'),
        ];
    }

    public static function headersFilter(array $array, array $entityHeaders)
    {
        $entityHeadersKeys = array_keys($entityHeaders);
        foreach ($array as $key => $item) {
            $itemName = is_object($item) ? $item->getName() : $key;
            $itemTitle = is_object($item) ? $item->getTitle() : $item['title'];
            $isReq = is_object($item) ? $item->isRequired() : $item['required'];
            if (in_array($itemName, $entityHeadersKeys)) {
                $entityHeaders[$itemName] = $isReq ? '*' . $itemTitle : $itemTitle;
            } elseif ($itemName === 'SITE_ID') {
                $entityHeaders['SITE'] = $isReq ? '*' . $itemTitle : $itemTitle;
            }
        }

        return $entityHeaders;
    }

    public static function morphyRuleForExport($rule)
    {
        $result = '';

        if ($rule['CLASS_ID'] === 'CondGroup') {
            $result .= '[';
        }

        if ($rule['CLASS_ID'] !== 'CondGroup') {
            if ($rule['DATA']['value']) {
                $result .= $rule['CLASS_ID'] . ':' . $rule['DATA']['logic'] . ':' . $rule['DATA']['value'];
            } else {
                $result .= $rule['CLASS_ID'] . ':' . $rule['DATA']['logic'];
            }
        }

        if ($rule['CHILDREN'] && is_countable($rule['CHILDREN'])) {
            $count = count($rule['CHILDREN']);
            foreach ($rule['CHILDREN'] as $childRule) {
                $result .= self::morphyRuleForExport($childRule);
                $count--;
                if ($count > 0) {
                    $result .= '{' . $rule['DATA']['All'] . '}';
                }
            }
        }

        if ($rule['CLASS_ID'] === 'CondGroup') {
            $result .= ']';
        }

        return $result;
    }

    public static function prepareForExportCondition(array $condition, array &$headers)
    {
        array_walk($condition, function ($cond, $key) use (&$headers) {
            if (!in_array($key, self::$skipKey) && $unser = unserialize($cond)) {
                $headers[$key] = implode(', ', $unser);
            } elseif (!in_array($key, self::$skipKey)) {
                $headers[$key] = $cond;
            }
        });
        if ($condition['META'] && $meta = unserialize($condition['META'])) {
            array_walk($meta, function ($cond, $key) use (&$headers) {
                if (!in_array($key, self::$textAreaType)) {
                    $headers[$key] = self::setHeaders($key, $cond);
                }
            });
            unset($condition['META']);
        }

        if ($condition['RULE'] && $rule = unserialize($condition['RULE'])) {
            $headers['RULE'] = self::morphyRuleForExport($rule);
        }

        $headers = Encoding::convertEncoding($headers, LANG_CHARSET, 'utf-8');
    }

    public static function prepareForExportCHPU(array $chpu, array &$headers)
    {
        array_walk($chpu, function ($chp, $key) use (&$headers) {
            if (!in_array($key, self::$skipKey) && $unser = unserialize($chp)) {
                $headers[$key] = implode(', ', $unser);
            } elseif (!in_array($key, self::$skipKey)) {
                if ($key === 'SITE_ID') {
                    $headers['SITE'] = $chp;
                } else {
                    $headers[$key] = $chp;
                }
            }
        });


        $replace = [];

        if ($chpu['CONDITION_META'] && $condMeta = unserialize($chpu['CONDITION_META'])) {
            $morphyObject = SeoMetaMorphy::morphyLibInit();
            $sku = new Section($chpu['section_id']);
            \CSeoMetaTagsProperty::$params = unserialize($chpu['PROPERTIES']);
            array_walk($condMeta, function ($chp, $key) use (&$headers, $morphyObject, $sku, &$replace) {
                if ($headers[$key] !== null) {
                    $chp = Engine::process($sku, SeoMetaMorphy::prepareForMorphy($chp));
                    $chp = SeoMetaMorphy::convertMorphy($chp, $morphyObject);
                    $chp = htmlspecialchars_decode($chp);
                    $headers[$key] = self::setHeaders($key, $chp);
                    $replace[] = $key;
                }
            });
        }

        if ($chpu['CHPU_META'] !== 'N' && $chpuMeta = unserialize($chpu['CHPU_META'])) {
            array_walk($chpuMeta, function ($chp, $key) use (&$headers, $chpuMeta, &$replace) {
                if ($headers[$key] !== null && $chpuMeta[$key . "_REPLACE"] === 'Y') {
                    $headers[$key] = self::setHeaders($key, $chp);
                    $replace[] = $key;
                }
            });
        }

        if ($chpu['CHPU_META'] === null || $chpu['CONDITION_META'] === null) {
            foreach ($headers as $key => &$header) {
                if (stripos($key, 'ELEMENT_') !== false && !in_array($key, $replace)) {
                    $header = '';
                }
            }
        }

        $headers = Encoding::convertEncoding($headers, LANG_CHARSET, 'utf-8');
    }

    public static function setHeaders($key, $chp)
    {
        if ($key === 'ELEMENT_FILE' && $chp) {
            $file = \CFile::GetFileArray($chp);
            return $file['SRC'];
        } else {
            return $chp;
        }
    }

    public static function pathToFile(array $entity, int $offset, array $entityHeaders, array $entityVal, string $sheetName = 'seometa_condition')
    {
        global $USER;
        $path = self::UPLOAD_TEMPLATE_DIR . $sheetName . $USER->GetID() . ".xlsx";
        $arrHeaders = self::headersFilter($entity, $entityHeaders);
        $arrHeadersName = array_keys($arrHeaders);
        $arrHeadersTitle = array_values($arrHeaders);
        array_walk($arrHeadersTitle, fn(&$name) => $name = Encoding::convertToUtf($name));

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            $mySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_SERVER['DOCUMENT_ROOT'] . $path);

            $worksheet = $mySpreadsheet->getSheetByName(Encoding::convertToUtf($sheetName));
        } else {
            $mySpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $mySpreadsheet->removeSheetByIndex(0);

            $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($mySpreadsheet,
                Encoding::convertToUtf($sheetName));
            $mySpreadsheet->addSheet($worksheet, 0);
        }

        $worksheet->fromArray([
            $arrHeadersName,
            $arrHeadersTitle
        ]);

        $count = $offset + 3;

        if ($isCond = ($sheetName === 'seometa_condition' || $sheetName === 'seometa_condition_example')) {
            $langVal = 'COND';
        } else {
            $langVal = 'CHPU';
        }

        if ($entityVal) {
            foreach ($entityVal as $val) {
                $headers = $entityHeaders;
                if ($isCond) {
                    self::prepareForExportCondition($val, $headers);
                } else {
                    self::prepareForExportCHPU($val, $headers);
                }
                $worksheet->fromArray([$headers], NULL, 'A' . $count);
                $count++;
            }
        }

        foreach ($worksheet->getColumnIterator() as $column) {
            $firstRow = $column->getColumnIndex() . 1;
            $secondRow = $column->getColumnIndex() . 2;
            if (strpos($worksheet->getCell($secondRow)->getValue(), '*') !== false) {
                $worksheet->getStyle($firstRow)->getFont()->setBold(true);
                $newVal = str_replace('*', '', $worksheet->getCell($secondRow)->getValue());
                $worksheet->setCellValue($secondRow, $newVal);
            }
            $thirdRow = $column->getColumnIndex() . 3;
            $value = $worksheet->getCell($firstRow)->getValue();
            if (in_array($value, $arrHeadersName) && ($offset === 0 || $offset - 3 === 0)) {
                $worksheet->getComment($secondRow)->getText()->createTextRun(Encoding::convertToUtf(Loc::getMessage('SEOMETA_EXPORTHELPER_' . $langVal . '_DESCRIPTION_' . $value)));
                $worksheet->getComment($thirdRow)->getText()->createTextRun(Encoding::convertToUtf(Loc::getMessage('SEOMETA_EXPORTHELPER_' . $langVal . '_EXAMPLE_' . $value)));
                $worksheet->getComment($secondRow)->setWidth('250');
                $worksheet->getComment($thirdRow)->setWidth('250');
                if($value === 'RULE'){
                    $worksheet->getComment($secondRow)->setHeight('450');
                    $worksheet->getComment($thirdRow)->setHeight('250');
                }else{
                    $worksheet->getComment($secondRow)->setHeight('100');
                    $worksheet->getComment($thirdRow)->setHeight('150');
                }
            }
            $worksheet->getColumnDimension($column->getColumnIndex())->setWidth(30);
            $worksheet->getCell($secondRow)->getStyle()->getAlignment()->setWrapText(true);
        }
        $range = $offset + count($entityVal) + 2;
        $worksheet->getStyle('A1' . ':' . $column->getColumnIndex() . $range)->getAlignment()->setWrapText(true);

        \CheckDirPath($_SERVER['DOCUMENT_ROOT'] . self::UPLOAD_TEMPLATE_DIR);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($mySpreadsheet);
        $writer->save($_SERVER['DOCUMENT_ROOT'] . $path);

        return [
            'NAME' => $sheetName . ".xlsx",
            'PATH' => $path
        ];
    }
}