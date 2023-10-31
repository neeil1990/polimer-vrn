<?php

namespace Sotbit\Seometa\Controllers;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Helper\ImportExport\ImportHelper;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use  Sotbit\Seometa\Helper\ImportExport\ExportHelper;


class ExcelExportImport extends Controller
{
    const MODULE_ID = 'sotbit.seometa';

    public function configureActions()
    {
        return [
            'exportCondition' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'exportCHPU' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'exportExampCond' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'exportExampCHPU' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'importCHPU' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'importCondition' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'deleteFile' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
        ];
    }

    public static function exportConditionAction(int $offset, int $limit, bool $newFile, $count, $totalCount)
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/vendor/autoload.php");
        require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/interface/admin_lib.php");

        try {
            $sheetName = 'seometa_condition';
            if ($newFile) {
                /*unlink($_SERVER['DOCUMENT_ROOT'] . ExportHelper::UPLOAD_TEMPLATE_DIR . $sheetName . ".xlsx");*/
                $count = ConditionTable::query()
                    ->addSelect('ID')
                    ->setFilter([])
                    ->queryCountTotal();
            }

            $arrCondition = ConditionTable::query()
                ->setSelect(ExportHelper::getConditionFields())
                ->setFilter([])
                ->setOrder(['SORT' => 'ASC'])
                ->setOffset($offset)
                ->setLimit($limit)
                ->fetchAll();

            $entityHeaders = ExportHelper::getConditionHeaders();
            $result = ExportHelper::pathToFile(ConditionTable::getMap(), $offset, $entityHeaders, $arrCondition);
            $countCHPU = count($arrCondition);
            if (($countRes = $count - $countCHPU) > 0) {
                $result['COUNT'] = $countRes;
                $result['OFFSET'] = $offset + $countCHPU;
                $result['TOTAL_COUNT'] = $newFile ? $count : $totalCount;
                $curPercent = 100 - intdiv((100 * $countRes), $result['TOTAL_COUNT']);
                $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_COND_RUN_INIT'), Loc::getMessage('SEO_META_COND_RUN_TITLE'), $curPercent);
            } elseif (($count - $countCHPU) == 0) {
                $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_RUN_FINISH'), Loc::getMessage('SEO_META_COND_RUN_TITLE'), 100);
            }
            return $result;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function exportCHPUAction(int $offset, int $limit, int $newFile, int $count, int $totalCount)
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/vendor/autoload.php");
        require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/interface/admin_lib.php");

        try {
            $sheetName = 'seometa_chpu';
            if ($newFile) {
                /*unlink($_SERVER['DOCUMENT_ROOT'] . ExportHelper::UPLOAD_TEMPLATE_DIR . $sheetName . ".xlsx");*/
                $count = SeometaUrlTable::query()
                    ->addSelect('ID')
                    ->setFilter([])
                    ->queryCountTotal();
            }

            $arrCHPU = SeometaUrlTable::query()
                ->setSelect(ExportHelper::getCHPUFields())
                ->addSelect('CHPU_SEODATA.SEOMETA_DATA', 'CHPU_META')
                ->addSelect('PARENT_CONDITION.META', 'CONDITION_META')
                ->setFilter([])
                ->setOrder(['ID' => 'ASC'])
                ->setOffset($offset)
                ->setLimit($limit)
                ->fetchAll();

            $entityHeaders = ExportHelper::getCHPUHeaders();
            $result = ExportHelper::pathToFile(SeometaUrlTable::getMap(), $offset, $entityHeaders, $arrCHPU, $sheetName);
            $countCHPU = count($arrCHPU);
            if (($countRes = $count - $countCHPU) > 0) {
                $result['COUNT'] = $countRes;
                $result['OFFSET'] = $offset + $countCHPU;
                $result['TOTAL_COUNT'] = $newFile ? $count : $totalCount;
                $curPercent = 100 - intdiv((100 * $countRes), $result['TOTAL_COUNT']);
                $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), $curPercent);
            } elseif (($count - $countCHPU) == 0) {
                $result['PROGRESSBAR'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_RUN_FINISH'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), 100);
            }
            return $result;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function importCHPUAction()
    {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if ($fileID = $request->get('file')) {
            $file = \CFile::GetByID($fileID)->fetch() ?: null;
        }

        try {
            if (!$file) {
                $result['ERRORS'] = Loc::getMessage('SEO_META_NO_SUCH_FILE');
                return $result;
            }
            return ImportHelper::parseCHPUFromExcelFile($file, $request->getValues());
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function importConditionAction()
    {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if ($fileID = $request->get('file')) {
            $file = \CFile::GetByID($fileID)->fetch() ?: null;
        }

        try {
            if (!$file) {
                $result['ERRORS'] = Loc::getMessage('SEO_META_NO_SUCH_FILE');
                return $result;
            }
            return ImportHelper::parseConditionFromExcelFile($file, $request->getValues());
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function deleteFileAction($sheetName)
    {
        global $USER;
        unlink($_SERVER['DOCUMENT_ROOT'] . ExportHelper::UPLOAD_TEMPLATE_DIR . $sheetName . $USER->GetID() . ".xlsx");
    }

    public static function exportExampCondAction()
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/vendor/autoload.php");

        try {
            $entityHeaders = ExportHelper::getConditionHeaders();
            return ExportHelper::pathToFile(ConditionTable::getMap(), 0, $entityHeaders, [], 'seometa_condition_example');
        } catch (\Exception $e){
            return $e;
        }
    }

    public static function exportExampCHPUAction()
    {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/vendor/autoload.php");

        try {
            $entityHeaders = ExportHelper::getCHPUHeaders();
            return ExportHelper::pathToFile(SeometaUrlTable::getMap(), 0, $entityHeaders, [], 'seometa_chpu_example');
        } catch (\Exception $e){
            return $e;
        }
    }
}
