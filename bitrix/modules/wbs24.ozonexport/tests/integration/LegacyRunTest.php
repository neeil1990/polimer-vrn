<?php
namespace Wbs24\Ozonexport;

/**
 * Для успешного прохождения интеграционных тестов требуется специальное тестовое окружение (БД)
 */
class LegacyRunTest extends BitrixTestCase
{
    // не используется в данный момент (попытка интеграционного теста с легиси кодом обернутым в класс)
    private function prepareAssertionDataOverClass($param, $resultFileName)
    {
        $ymlFile = $param['SETUP_FILE_NAME'];

        // заглушки

        // вычисление результата
        $ozonRun = new LegacyRun();
        $ozonRun->run($param);
        $result = file_get_contents($_SERVER['DOCUMENT_ROOT'].$ymlFile);
        $result = $this->repalaceDate($result);

        unlink($_SERVER['DOCUMENT_ROOT'].$ymlFile);

        // результат для проверки
        $expectedResult = file_get_contents(__DIR__.'/files/'.$resultFileName.'.php');
        $expectedResult = $this->repalaceDate($expectedResult);
        $expectedResult = str_replace("\r\n", "\n", $expectedResult);

        return [$expectedResult, $result];
    }

    private function prepareAssertionData($param, $resultFileName)
    {
        $ymlFile = $param['SETUP_FILE_NAME'];

        // подготовка параметров
        foreach ($param as $name => $value) $$name = $value;

        // заглушки
        $wrappers = new Wrappers();

        // вычисление результата
        require($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/catalog_export/ozon_run.php');
        $fileLink = file_get_contents($_SERVER['DOCUMENT_ROOT'].$ymlFile);
        $fileLink = str_replace(['<?php require("', '");'."\n"], '', $fileLink);
        $result = file_get_contents($fileLink);
        $result = $this->repalaceDate($result);

        unlink($_SERVER['DOCUMENT_ROOT'].$ymlFile);
        unlink($_SERVER['DOCUMENT_ROOT'].$ymlFile.'.txt');
        unlink($fileLink);

        // результат для проверки
        $expectedResult = file_get_contents(__DIR__.'/files/'.$resultFileName.'.php');

        // если раскоментировать эту строку, то будут созданы отсутствующие фаилы для проверки, на основе вычесленного результата
        // используется только для обновления тестов, на основе проверенных результатов
        //if (!$expectedResult) file_put_contents(__DIR__.'/files/'.$resultFileName.'.php', $result);

        $expectedResult = $this->repalaceDate($expectedResult);
        $expectedResult = str_replace("\r\n", "\n", $expectedResult);

        return [$expectedResult, $result];
    }

    private function repalaceDate($str)
    {
        $date = "2021-11-09 23:35";
        $str = preg_replace(
            '/yml_catalog date=\"([0-9 :-]+)\"/',
            'yml_catalog date="'.$date.'"',
            $str
        );

        return $str;
    }

    private function getStandartParam($ymlFile)
    {
        return [
            "SETUP_FILE_NAME" => $ymlFile,
            "V" => [
                "0" => "16",
            ],
            "IBLOCK_ID" => "2",
            "SITE_ID" => "s1",
            "SETUP_SERVER_NAME" => "santehstroy",
            "COMPANY_NAME" => "",
            "XML_DATA" => 'a:7:{s:4:"TYPE";s:4:"none";s:8:"XML_DATA";a:0:{}s:8:"CURRENCY";a:5:{s:3:"RUB";a:2:{s:4:"rate";s:4:"SITE";s:4:"plus";N;}s:3:"USD";a:2:{s:4:"rate";s:4:"SITE";s:4:"plus";N;}s:3:"EUR";a:2:{s:4:"rate";s:4:"SITE";s:4:"plus";N;}s:3:"UAH";a:2:{s:4:"rate";s:4:"SITE";s:4:"plus";N;}s:3:"BYR";a:2:{s:4:"rate";s:4:"SITE";s:4:"plus";N;}}s:5:"PRICE";i:0;s:10:"SKU_EXPORT";a:2:{s:15:"SKU_EXPORT_COND";s:1:"1";s:13:"SKU_PROP_COND";a:3:{s:7:"PROP_ID";i:0;s:4:"COND";s:0:"";s:6:"VALUES";a:0:{}}}s:10:"VAT_EXPORT";a:2:{s:6:"ENABLE";s:1:"N";s:8:"BASE_VAT";s:0:"";}s:13:"COMMON_FIELDS";a:1:{s:11:"DESCRIPTION";s:12:"PREVIEW_TEXT";}}',
            "USE_HTTPS" => "N",
            "FILTER_AVAILABLE" => "N",
            "DISABLE_REFERERS" => "N",
            "EXPORT_CHARSET" => "UTF-8",
            "MAX_EXECUTION_TIME" => "0",
            "SET_ID" => "ARTNUMBER",
            "SET_OFFER_ID" => "XML_ID",
            "MIN_STOCK" => "3",
            "IGNORE_SALE" => "N",
            "CONDITIONS" => "",
            "CHECK_PERMISSIONS" => "N",
            "BLOB" => [
                "extendPrice" => "N",
                "plusPercent" => "10",
                "plusAdditionalSum" => "100",
                "oldPricePlusPercent" => "10",
                "oldPrice10kPlusPercent" => "5",
                "premiumPriceMinusPercent" => "20",
                "newMinPriceMinusPercent" => "",

                "warehouseDefaultName" => "",
                "extendWarehouse" => "N",
                'extendWarehouseFilter' => "N",
                'warehouseId1Active' => "N",
                'warehouseId1Name' => "",
                'warehouseId2Active' => "N",
                'warehouseId2Name' => "",

                'limitPriceOn' => "N",
                'limitMinPrice' => 1000,
                'limitMaxPrice' => 5000,
                'limitPriceBeforeExtPrice' => "N",

                'filterOn' => 'N',
            ],
        ];
    }

    private function clearOfferLog($db, $profileId)
    {
        $where = [
            'profile_id' => $profileId,
        ];
        $db->clear('wbs24_ozonexport_offers_log', $where);
    }

    public function testRun()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_test_".time().".php";
        $param = $this->getStandartParam($ymlFile);

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_run_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunWithIgnoreSale()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testIgnoreSale_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["IGNORE_SALE"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_runIgnoreSale_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedPrice()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtPrice_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["BLOB"]["extendPrice"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_runExtPrice_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedPriceWithIgnoreSale()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtPriceIgnoreSale_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["IGNORE_SALE"] = "Y";
        $param["BLOB"]["extendPrice"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_runExtPriceIgnoreSale_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedWarehouse()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtWarehouse_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["BLOB"]["extendWarehouse"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testExtWarehouse_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedPriceAndWarehouse()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtPriceExtWarehouse_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["BLOB"]["extendPrice"] = "Y";
        $param["BLOB"]["extendWarehouse"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testExtPriceExtWarehouse_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedPriceAndLimitPrice()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtPriceLimit_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["IGNORE_SALE"] = "Y";
        $param["BLOB"]["extendPrice"] = "Y";
        $param["BLOB"]["limitPriceOn"] = "Y";
        $param["BLOB"]["limitPriceBeforeExtPrice"] = "Y";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testExtPriceLimitPrice_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunExtendedPriceWithMinPrice()
    {
		// входные параметры
        $ymlFile = "/upload/ozon_testExtPriceWithMinPrice_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param["BLOB"]["extendPrice"] = "Y";
        $param["BLOB"]["plusPercent"] = "";
        $param["BLOB"]["plusAdditionalSum"] = "";
        $param["BLOB"]["oldPricePlusPercent"] = "";
        $param["BLOB"]["oldPrice10kPlusPercent"] = "";
        $param["BLOB"]["newMinPriceMinusPercent"] = "10";

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_runExtPriceWithMinPrice_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunOffersLog()
    {
        // общие входные параметры
        $db = new Db();
        $profileId = 999999;

        // очистить offers log
        $this->clearOfferLog($db, $profileId);

        // step 1 - выгрузка всех позиций
        // входные параметры
        $ymlFile = "/upload/ozon_testOffersLog_step1_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['PROFILE_ID'] = $profileId;
        $param["BLOB"]["offersLogOn"] = "Y";
        $param["BLOB"]["nullOfferLifetimeDays"] = 1;

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testOffersLog_step1_result');

        // проверка
        $this->assertEquals($expectedResult, $result);

        // step 2 - исключаем позиции - они должны отобразится в конце с 0-ым остатком
        // входные параметры
        $ymlFile = "/upload/ozon_testOffersLog_step2_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['PROFILE_ID'] = $profileId;
        $param["BLOB"]["offersLogOn"] = "Y";
        $param["BLOB"]["nullOfferLifetimeDays"] = 1;
        $param["BLOB"]["limitPriceOn"] = "Y";
        $param["BLOB"]["limitMinPrice"] = 750;

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testOffersLog_step2_result');

        // проверка
        $this->assertEquals($expectedResult, $result);

        // pre step 3 - подготовка к шагу
        // имитация обнуления остатков более суток назад
        $data = [
            'null_export_time' => 1,
        ];
        $where = [
            'profile_id' => $profileId,
            'normal_export_time' => 0,
        ];
        $db->update('wbs24_ozonexport_offers_log', $data, $where);

        // step 3 - исключаем позиции - они больше не должны отображаться
        // входные параметры
        $ymlFile = "/upload/ozon_testOffersLog_step3_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['PROFILE_ID'] = $profileId;
        $param["BLOB"]["offersLogOn"] = "Y";
        $param["BLOB"]["nullOfferLifetimeDays"] = 1;
        $param["BLOB"]["limitPriceOn"] = "Y";
        $param["BLOB"]["limitMinPrice"] = 750;

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_testOffersLog_step3_result');

        // проверка
        $this->assertEquals($expectedResult, $result);

        // очистить offers log
        $this->clearOfferLog($db, $profileId);
    }
}
