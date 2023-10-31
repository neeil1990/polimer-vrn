<?php
namespace Wbs24\Ozonexport;

class CommonWarehouseTest extends BitrixTestCase
{
    public function testGetXml()
    {
		// входные параметры
		$availableQuantityArray = [-1, 2, 3, 10];
		$minStock = 3;

		// результат для проверки
		$expectedResultArray = [0, 0, 3, 10];

		// заглушка

		// обход условий
		$warehouse = new CommonWarehouse([
			'minStock' => $minStock,
		]);
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$product = [];
			$product['QUANTITY'] = $availableQuantityArray[$k];
			$xml = $warehouse->getXml($product);

			// получить результат из xml
			$dom = new \DOMDocument;
			$dom->loadXML($xml);
			$outlets = $dom->getElementsByTagName('outlets');
			if ($outlets->length == 1) {
				$outletsElement = $outlets->item(0);
				$outlet = $outletsElement->getElementsByTagName('outlet');

				if ($outlet->length == 1) {
					$outletElement = $outlet->item(0);
					$result = $outletElement->getAttribute('instock');
				}
			}

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetXmlWithWarehouseName()
    {
		// входные параметры
		$minStock = 3;
		$warehouseName = 'Stock 1';
		$product = [
			'QUANTITY' => 3,
		];

		// результат для проверки
		$expectedResult = "<outlets><outlet instock=\"3\" warehouse_name=\"Stock 1\"></outlet></outlets>\n";

		// заглушка

		// вычисление результата
		$warehouse = new CommonWarehouse([
			'minStock' => $minStock,
			'warehouseDefaultName' => $warehouseName,
		]);
		$result = $warehouse->getXml($product);

		// проверка
		$this->assertEquals($expectedResult, $result);
    }
}