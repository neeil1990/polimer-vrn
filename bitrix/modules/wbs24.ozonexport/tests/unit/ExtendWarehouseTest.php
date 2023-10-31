<?php
namespace Wbs24\Ozonexport;

class ExtendWarehouseTest extends BitrixTestCase
{
    public function testGetXml()
    {
		// входные параметры
		$productId = 1;
		$availableQuantityArray = [3, 3, 10];
		$minStock = 3;
		$inStockArray = [
			[2, 1],
			[3, 0],
			[3, 7],
		];

		// результат для проверки
		$expectedInStockArray = [
			[0, 0],
			[3, 0],
			[3, 7],
		];
		$expectedResultArray = [];
		foreach ($expectedInStockArray as $expectedInStock) {
			$expectedResult = [];
			foreach ($expectedInStock as $k => $inStock) {
				$expectedResult[] = [
					'instock' => $inStock,
					'warehouse_name' => 'Склад '.($k + 1),
				];
			}
			$expectedResultArray[] = $expectedResult;
		}

		// заглушки
		$DBResultStubs = [];
		foreach ($inStockArray as $inStock) {
			$DBResultStub = $this->createMock(\Bitrix\Main\DB\Result::class);
			$fetchResults = [];
			foreach ($inStock as $k => $stock) {
				$fetchResults[] = [
					'AMOUNT' => $stock,
					'STORE_ID' => ($k + 1),
					'STORE_TITLE' => 'Склад '.($k + 1),
				];
			}
			$fetchResults[] = false;
			$DBResultStub->method('Fetch')
				->will($this->onConsecutiveCalls(...$fetchResults));
			$DBResultStubs[] = $DBResultStub;
		}

		$StoreProductTableStub = $this->createMock(StoreProductTable::class);
		$StoreProductTableStub->method('getList')
			->will($this->onConsecutiveCalls(...$DBResultStubs));

		// объект для получения результата
		$warehouse = new ExtendWarehouse([
			'minStock' => $minStock,
			'objects' => [
				'StoreProductTable' => $StoreProductTableStub,
			],
		]);
		// обход условий
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$product = [];
			$product['ID'] = $productId;
			$product['QUANTITY'] = $availableQuantityArray[$k];
			$xml = $warehouse->getXml($product);

			// получить результат из xml
			$result = [];
			$dom = new \DOMDocument;
			$dom->loadXML($xml);
			$outlets = $dom->getElementsByTagName('outlets');
			if ($outlets->length == 1) {
				$outletsElement = $outlets->item(0);
				$outlet = $outletsElement->getElementsByTagName('outlet');

				foreach ($outlet as $outletElement) {
					$result[] = [
						'instock' => $outletElement->getAttribute('instock'),
						'warehouse_name' => $outletElement->getAttribute('warehouse_name'),
					];
				}
			}

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetStockInWarehouses()
    {
		// входные параметры
        $productId = 1;
        $stocks = [
            [
                'AMOUNT' => 10,
                'STORE_ID' => 1,
                'STORE_TITLE' => 'Store 1',
            ],
        ];
        $warehouses = [
            [
                'ID' => 1,
                'TITLE' => 'Store 1',
            ],
            [
                'ID' => 2,
                'TITLE' => 'Store 2',
            ],
        ];

		// результат для проверки
		$expectedResult = [
            [
                'AMOUNT' => 10,
                'STORE_ID' => 1,
                'STORE_TITLE' => 'Store 1',
            ],
            [
                'AMOUNT' => 0,
                'STORE_ID' => 2,
                'STORE_TITLE' => 'Store 2',
            ],
        ];;

        // заглушки
        // для StoreTable
        $DBResultStub = $this->createMock(\Bitrix\Main\DB\Result::class);
        $fetchResults = [];
        foreach ($warehouses as $warehouse) {
            $fetchResults[] = $warehouse;
        }
        $fetchResults[] = false;
        $DBResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $StoreTableStub = $this->createMock(StoreTable::class);
        $StoreTableStub->method('getList')
            ->willReturn($DBResultStub);

        // для StoreProductTable
        $DBResultStub = $this->createMock(\Bitrix\Main\DB\Result::class);
        $fetchResults = [];
        foreach ($stocks as $stock) {
            $fetchResults[] = $stock;
        }
        $fetchResults[] = false;
        $DBResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $StoreProductTableStub = $this->createMock(StoreProductTable::class);
        $StoreProductTableStub->method('getList')
            ->willReturn($DBResultStub);

        // вычисление результата
        $warehouse = new ExtendWarehouse([
            'objects' => [
                'StoreTable' => $StoreTableStub,
                'StoreProductTable' => $StoreProductTableStub,
            ],
        ]);
        $result = $warehouse->getStockInWarehouses($productId);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testFilterAndRenameWarehouses()
    {
		// входные параметры
        $param = [
            'extendWarehouse' => true,
            'extendWarehouseFilter' => true,
            'warehouseId1Active' => true,
            'warehouseId1Name' => 'Main',
            'warehouseId2Active' => false,
            'warehouseId2Name' => 'Additional',
            'warehouseId3Active' => true,
            'warehouseId3Name' => '',
        ];
        $stocks = [
            [
                'AMOUNT' => 10,
                'STORE_ID' => 1,
                'STORE_TITLE' => 'Store 1',
            ],
            [
                'AMOUNT' => 15,
                'STORE_ID' => 2,
                'STORE_TITLE' => 'Store 2',
            ],
            [
                'AMOUNT' => 20,
                'STORE_ID' => 3,
                'STORE_TITLE' => 'Store 3',
            ],
        ];

		// результат для проверки
		$expectedResult = [
            [
                'AMOUNT' => 10,
                'STORE_ID' => 1,
                'STORE_TITLE' => 'Main',
            ],
            [
                'AMOUNT' => 20,
                'STORE_ID' => 3,
                'STORE_TITLE' => 'Store 3',
            ],
        ];;

		// заглушка

        // вычисление результата
        $warehouse = new ExtendWarehouse($param);
        $result = $warehouse->filterAndRenameWarehouses($stocks);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
