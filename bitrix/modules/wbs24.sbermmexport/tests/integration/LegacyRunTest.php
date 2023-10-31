<?php
namespace Wbs24\Sbermmexport;

/**
 * Для успешного прохождения интеграционных тестов требуется специальное тестовое окружение (БД)
 */
class LegacyRunTest extends BitrixTestCase
{
    protected function prepareAssertionData($param, $resultFileName)
    {
        $ymlFile = $param['SETUP_FILE_NAME'];

        // подготовка параметров
        foreach ($param as $name => $value) {
            if ($name == 'XML_DATA') $value = serialize($value);
            $$name = $value;
        }

        // заглушки
        $wrappers = new Wrappers();

        // вычисление результата
        require($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/catalog_export/sbermm_run.php');
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

    protected function repalaceDate($str)
    {
        $date = "2021-11-09 23:35";
        $str = preg_replace(
            '/yml_catalog date=\"([0-9 :-]+)\"/',
            'yml_catalog date="'.$date.'"',
            $str
        );

        return $str;
    }

    protected function getStandartParam($ymlFile)
    {
        return [
            "SETUP_FILE_NAME" => $ymlFile,
            "V" => [
                "0" => "27",
            ],
            "IBLOCK_ID" => "2",
            "SITE_ID" => "s2",
            "SETUP_SERVER_NAME" => "demo",
            "COMPANY_NAME" => "",
            // позже массив сериализуется в функции prepareAssertionData()
            "XML_DATA" => array(
                'TYPE' => 'none',
                'XML_DATA' => array(
                    'days' => 1127
                ),
                'CURRENCY' => array(
                    'RUB' => array(
                        'rate' => 'SITE',
                        'plus' => null
                    ),
                    'USD' => array(
                        'rate' => 'SITE',
                        'plus' => null
                    ),
                    'EUR' => array(
                        'rate' => 'SITE',
                        'plus' => null
                    ),
                    'UAH' => array(
                        'rate' => 'SITE',
                        'plus' => null
                    ),
                    'BYN' => array(
                        'rate' => 'SITE',
                        'plus' => null
                    )
                ),
                'PRICE' => 0,
                'SKU_EXPORT' => array(
                    'SKU_EXPORT_COND' => 1,
                    'SKU_PROP_COND' => array(
                        'PROP_ID' => 0,
                        'COND' => '',
                        'VALUES' => array(
                        )
                    )
                ),
                'VAT_EXPORT' => array(
                    'ENABLE' => 'N',
                    'BASE_VAT' => ''
                ),
                'COMMON_FIELDS' => array(
                    'PICTURE' => 'AUTO',
                    'DESCRIPTION' => 'PREVIEW_TEXT'
                )
            ),
            "USE_HTTPS" => "N",
            "FILTER_AVAILABLE" => "N",
            "DISABLE_REFERERS" => "Y",
            "EXPORT_CHARSET" => "UTF-8",
            "MAX_EXECUTION_TIME" => "0",
            "SET_ID" => "ID",
            "SET_OFFER_ID" => "ID",
            "MIN_STOCK" => "0",
            "ORDER_BEFORE" => "",
            "DELIVERY_DAYS" => "",
            "STORE_ID" => "0",
            "CONDITIONS" => "",
            "CHECK_PERMISSIONS" => "N",
            "BLOB" => [
                "ignoreSale" => "N",

                "extendPriceByFormula" => "N",
                "formulaPrice" => "",
                "formulaOldPrice" => "",

                "extendPrice" => "N",
                "plusPercent" => "",
                "plusAdditionalSum" => "",
                "oldPricePlusPercent" => "",

                "extendWarehouse" => "N",
                "extendWarehouseFilter" => "N",
                "warehouseId1Active" => "N",
                "propertiesBasedWarehouse" => "N",
                "stocksProp1" => "",
                "stocksProp2" => "",
                "stocksProp3" => "",
                "stocksProp4" => "",
                "stocksProp5" => "",

                "limitPriceOn" => "N",
                "limitMinPrice" => "",
                "limitMaxPrice" => "",
                "limitPriceBeforeExtPrice" => "N",

                "filterOn" => 'N',
                "conditions" => "",
            ],
        ];
    }

    public function testRun()
    {
        // входные параметры
        $ymlFile = "/upload/sbermm_test_".time().".php";
        $param = $this->getStandartParam($ymlFile);

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_run_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunWithOfferIdAsProperty()
    {
        // входные параметры
        $ymlFile = "/upload/sbermm_test_offerid_as_property_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['SET_ID'] = 'ARTNUMBER';
        $param['SET_OFFER_ID'] = 'ARTNUMBER';

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_run_offerid_as_property_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunWithPriceByFormula()
    {
        // входные параметры
        $ymlFile = "/upload/sbermm_test_price_by_formula_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['BLOB']['extendPriceByFormula'] = 'Y';
        $param['BLOB']['formulaPrice'] = '{PRICE} + 100';
        $param['BLOB']['formulaOldPrice'] = '({PRICE_DISCOUNT} - ({PRICE_DISCOUNT} - {PRICE})) * 1.2';

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_run_price_by_formula_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunWithPackageRatio()
    {
        // входные параметры
        $ymlFile = "/upload/sbermm_test_package_ratio_".time().".php";
        $param = $this->getStandartParam($ymlFile);
        $param['XML_DATA']['COMMON_FIELDS']['PRODUCT_PACKAGE_RATIO_PROPERTY'] = 35;

        list($expectedResult, $result) = $this->prepareAssertionData($param, 'LegacyRun_run_package_ratio_result');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
